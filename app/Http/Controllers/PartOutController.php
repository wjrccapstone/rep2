<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\JobOrder;
use App\Models\PartOut;
use App\Models\PartOutItem;
use App\Models\Product;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartOutController extends Controller
{
    /**
     * Data for the Parts Out tab (Product Inventory → Parts Out): parts consumed on a
     * job order, logged separately from a Cashier sale so a technician's usage is
     * on record the moment it happens, independent of when the job gets billed.
     */
    public function data(Request $request): array
    {
        $user = Auth::user();
        $isTechnician = $user?->role === 'technician';

        $query = PartOut::with(['jobOrder.customer', 'jobOrder.technician.user', 'jobOrder.device', 'items', 'approvedBy', 'rejectedBy'])
            ->withCount('items');

        // Technicians only ever see the requests they submitted themselves —
        // admins (reviewing and billing) see the full queue.
        if ($isTechnician) {
            $query->where('issued_by', $user->id);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('jobOrder', function ($j) use ($search) {
                        $j->where('code', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', "%{$search}%"));
            });
        }

        $range = $request->query('range', '');
        if ($range === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($range === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($range === 'month') {
            $query->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
        }

        if ($technicianId = $request->query('technician')) {
            $query->whereHas('jobOrder', fn ($j) => $j->where('technician_id', $technicianId));
        }

        $partOuts = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();

        $jobOrders = JobOrder::with(['customer', 'technician.user', 'service', 'device'])
            // Parts only get issued while a job is still being worked on — a completed
            // or cancelled job order has no more use for them.
            ->whereNotIn('status', ['cancelled', 'completed'])
            // Technicians can only log parts against their own job orders.
            ->when($isTechnician, fn ($q) => $q->where('technician_id', $user->technician?->id))
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        $products = Product::whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'price', 'stock']);

        $technicians = Technician::with('user')->whereHas('jobOrders')->get();

        return [
            'partOuts' => $partOuts,
            'jobOrders' => $jobOrders,
            'partOutProducts' => $products->mapWithKeys(fn ($p) => [
                $p->id => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'stock' => (int) $p->stock,
                ],
            ]),
            'technicians' => $technicians,
            'stats' => [
                'issuedToday' => (int) PartOutItem::whereHas('partOut', fn ($q) => $q->whereDate('created_at', today()))->sum('quantity'),
                'costThisMonth' => (float) PartOut::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total_cost'),
                'pendingApproval' => PartOut::where('status', 'pending_approval')
                    ->when($isTechnician, fn ($q) => $q->where('issued_by', $user->id))
                    ->count(),
                'lowStockProducts' => Product::whereNull('archived_at')->where('availability', 'low_stock')->count(),
            ],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'job_order_id' => ['required', 'exists:job_orders,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Technicians submit a request that waits for an admin's decision — stock
        // stays put until it's approved. Admins already have full inventory
        // authority, so their own entries go straight to pending billing, same
        // as before this approval flow existed.
        $requiresApproval = Auth::user()?->role === 'technician';

        $partOut = DB::transaction(function () use ($validated, $requiresApproval) {
            $jobOrder = JobOrder::findOrFail($validated['job_order_id']);

            $total = 0.0;
            $resolved = [];
            foreach ($validated['items'] as $row) {
                $product = Product::lockForUpdate()->findOrFail($row['product_id']);
                $qty = (int) $row['quantity'];
                $lineTotal = round((float) $product->price * $qty, 2);
                $total += $lineTotal;
                $resolved[] = [$product, $qty, $lineTotal];
            }

            $partOut = PartOut::create([
                'code' => PartOut::nextCode(),
                'job_order_id' => $jobOrder->id,
                'issued_by' => Auth::id(),
                'total_cost' => round($total, 2),
                'status' => $requiresApproval ? 'pending_approval' : 'pending_billing',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($resolved as [$product, $qty, $lineTotal]) {
                PartOutItem::create([
                    'part_out_id' => $partOut->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                if (! $requiresApproval && $product->stock > 0) {
                    // save() (not decrement()) so the Product "saving" hook recomputes availability.
                    $product->stock = max(0, $product->stock - $qty);
                    $product->save();
                }
            }

            if (! $requiresApproval) {
                // The part belongs to the shop's own stock, so its cost is billed to the
                // customer as part of the job order's total — same moment the stock leaves.
                $jobOrder->increment('cost', $partOut->total_cost);
                $jobOrder->syncPaymentStatus();
            }

            return $partOut;
        });

        $partOut->load(['jobOrder', 'items']);
        $itemCount = $partOut->items->count();

        ActivityLog::record([
            'module' => 'parts_out',
            'action' => $requiresApproval ? 'requested' : 'issued',
            'reference' => $partOut->code,
            'title' => $requiresApproval
                ? "Parts Out requested for job order {$partOut->jobOrder->code}"
                : "Parts issued for job order {$partOut->jobOrder->code}",
            'detail' => $itemCount.' '.Str::plural('part', $itemCount),
            'after_value' => 'PHP '.number_format((float) $partOut->total_cost, 2),
        ]);

        $message = $requiresApproval
            ? "Parts Out {$partOut->code} submitted for admin approval — ₱".number_format((float) $partOut->total_cost, 2).'.'
            : "Parts Out {$partOut->code} recorded — ₱".number_format((float) $partOut->total_cost, 2).'.';

        return redirect()->route('products.index', ['tab' => 'parts-out'])->with('status', $message);
    }

    /**
     * Admin accepts a technician's Parts Out request: stock is deducted now (it was
     * never touched at submission time) and the request moves into the normal
     * pending-billing flow.
     */
    public function approve(PartOut $partOut): RedirectResponse
    {
        if ($partOut->status !== 'pending_approval') {
            return redirect()->route('products.index', ['tab' => 'parts-out'])
                ->with('error', "{$partOut->code} has already been decided.");
        }

        DB::transaction(function () use ($partOut) {
            $partOut->load(['items', 'jobOrder']);

            foreach ($partOut->items as $item) {
                $product = $item->product_id ? Product::lockForUpdate()->find($item->product_id) : null;

                if ($product && $product->stock > 0) {
                    $product->stock = max(0, $product->stock - $item->quantity);
                    $product->save();
                }
            }

            $partOut->update([
                'status' => 'pending_billing',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // The part belongs to the shop's own stock, so its cost is billed to the
            // customer as part of the job order's total — same moment the stock leaves.
            $partOut->jobOrder->increment('cost', $partOut->total_cost);
            $partOut->jobOrder->syncPaymentStatus();
        });

        ActivityLog::record([
            'module' => 'parts_out',
            'action' => 'approved',
            'reference' => $partOut->code,
            'title' => "Parts Out {$partOut->code} approved",
        ]);

        return redirect()->route('products.index', ['tab' => 'parts-out'])
            ->with('status', "{$partOut->code} approved — stock has been deducted.");
    }

    /**
     * Admin declines a technician's request. Stock was never touched at submission
     * time, so there's nothing to roll back — just record why.
     */
    public function reject(Request $request, PartOut $partOut): RedirectResponse
    {
        if ($partOut->status !== 'pending_approval') {
            return redirect()->route('products.index', ['tab' => 'parts-out'])
                ->with('error', "{$partOut->code} has already been decided.");
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $partOut->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        ActivityLog::record([
            'module' => 'parts_out',
            'action' => 'rejected',
            'reference' => $partOut->code,
            'title' => "Parts Out {$partOut->code} rejected",
            'detail' => $validated['reason'],
        ]);

        return redirect()->route('products.index', ['tab' => 'parts-out'])
            ->with('status', "{$partOut->code} rejected.");
    }

    public function markBilled(PartOut $partOut): RedirectResponse
    {
        $partOut->update(['status' => 'billed']);

        ActivityLog::record([
            'module' => 'parts_out',
            'action' => 'billed',
            'reference' => $partOut->code,
            'title' => "Parts Out {$partOut->code} marked as billed",
        ]);

        return redirect()->route('products.index', ['tab' => 'parts-out'])
            ->with('status', "{$partOut->code} marked as billed.");
    }

    public function exportCsv(Request $request)
    {
        $partOuts = PartOut::with(['jobOrder.customer', 'items'])->orderByDesc('created_at')->get();

        $filename = 'parts-out-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($partOuts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['code', 'date', 'job_order', 'customer', 'product_name', 'quantity', 'unit_price', 'line_total', 'status']);
            foreach ($partOuts as $partOut) {
                foreach ($partOut->items as $item) {
                    fputcsv($handle, [
                        $partOut->code,
                        $partOut->created_at->format('Y-m-d'),
                        $partOut->jobOrder->code,
                        $partOut->jobOrder->customer->business_name ?: $partOut->jobOrder->customer->name,
                        $item->product_name,
                        $item->quantity,
                        $item->unit_price,
                        $item->line_total,
                        $partOut->statusLabel(),
                    ]);
                }
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
