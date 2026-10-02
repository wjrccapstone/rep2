<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\JobOrder;
use App\Models\PartOut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ForecastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CashierController extends Controller
{
    /**
     * Data for the Product Transaction tab (Product Inventory → Product Transaction).
     * Two sale types share one till:
     *  - walk_in    → product-only sale, job_order_id null, labor_cost 0
     *  - job_order  → bills an existing job order's service cost as labor_cost,
     *                 optionally with add-on products, and settles the job order.
     */
    public function transactionData(Request $request, ForecastService $forecast): array
    {
        $products = Product::query()
            ->with('category:id,name')
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category_id', 'price', 'stock', 'on_order', 'availability', 'image_path']);

        $categories = $products->pluck('category.name')->filter()->unique()->values();

        $billableJobOrders = JobOrder::query()
            ->with([
                'customer:id,name,business_name',
                'service:id,name',
                'device',
                // Parts an admin has already approved for this job but that haven't been
                // billed to the customer yet — shown as their own lines in the cart,
                // same as the parts cost already folded into the job's total.
                'partOuts' => fn ($q) => $q->where('status', 'pending_billing')->with('items'),
            ])
            ->withSum('sales as paid_total', 'amount_paid')
            ->where('payment_status', 'partial')
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'code', 'customer_id', 'service_id', 'status', 'payment_status', 'cost']);

        $todaySales = Sale::whereDate('sold_at', today());

        $posProducts = $products->mapWithKeys(fn ($p) => [
            $p->id => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
            ],
        ]);

        return [
            'products' => $products,
            'posProducts' => $posProducts,
            'categories' => $categories,
            'billableJobOrders' => $billableJobOrders,
            'nextNumber' => Sale::nextNumber(),
            'nextCode' => Sale::nextCode(),
            'salesTodayTotal' => (float) $todaySales->sum('total'),
            'transactionsToday' => (int) $todaySales->count(),
            'dataset' => $this->forecastDataset($forecast),
        ];
    }

    public function sale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_type' => ['required', Rule::in(Sale::SALE_TYPES)],
            'job_order_id' => ['nullable', Rule::requiredIf($request->input('sale_type') === 'job_order'), 'exists:job_orders,id'],
            'payment_method' => ['required', Rule::in(Sale::PAYMENT_METHODS)],
            'is_paid' => ['nullable', 'boolean'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'items' => ['array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'part_out_ids' => ['array'],
            'part_out_ids.*' => ['integer', 'exists:part_outs,id'],
        ]);

        $isJobOrder = $validated['sale_type'] === 'job_order';
        $lineItems = $validated['items'] ?? [];

        if (! $isJobOrder && count($lineItems) === 0) {
            return back()->with('error', 'Add at least one product before completing a walk-in sale.');
        }

        if ($isJobOrder) {
            $jobOrderCheck = JobOrder::find($validated['job_order_id']);
            if ($jobOrderCheck && $jobOrderCheck->balanceDue() <= 0 && count($lineItems) === 0) {
                return back()->with('error', "Job order {$jobOrderCheck->code} is already fully paid.");
            }
        }

        $billedPartOuts = collect();

        $sale = DB::transaction(function () use ($validated, $isJobOrder, $lineItems, $request, &$billedPartOuts) {
            $jobOrder = $isJobOrder ? JobOrder::lockForUpdate()->findOrFail($validated['job_order_id']) : null;

            $itemsTotal = 0.0;
            $resolved = [];
            foreach ($lineItems as $row) {
                $product = Product::lockForUpdate()->findOrFail($row['product_id']);
                $qty = (int) $row['quantity'];
                $lineTotal = (float) $product->price * $qty;
                $itemsTotal += $lineTotal;
                $resolved[] = [$product, $qty, $lineTotal];
            }

            // Bill only what's still owed on the job order — its balance after any
            // earlier partial payments — not the full service cost again.
            $laborCost = $isJobOrder ? $jobOrder->balanceDue() : 0.0;

            // A walk-in product sale is always paid in full on the spot — "on account"
            // only makes sense for a job order, which carries a running balance across
            // visits. Ignore whatever the client sent for a walk-in, regardless of tampering.
            $isPaid = $isJobOrder
                ? ($request->has('is_paid') ? $request->boolean('is_paid') : true)
                : true;

            $totals = $this->computeTotals($itemsTotal, $laborCost);
            $total = $totals['total'];

            if ($isPaid) {
                // Fully settled: cash tendered drives the change, the whole total is paid.
                $tendered = $request->filled('amount_tendered') ? round((float) $validated['amount_tendered'], 2) : null;
                $changeDue = $tendered !== null ? max(0, round($tendered - $total, 2)) : 0.0;
                $amountPaid = $total;
                $balanceDue = 0.0;
            } else {
                // Partial / on account: the customer pays part now, the rest is owed.
                $amountPaid = round(min((float) $request->input('amount_paid', 0), $total), 2);
                $amountPaid = max(0, $amountPaid);

                if ($amountPaid >= $total) {
                    // Paid in full after all — record it as settled.
                    $isPaid = true;
                    $tendered = $amountPaid;
                    $changeDue = 0.0;
                    $amountPaid = $total;
                    $balanceDue = 0.0;
                } else {
                    $tendered = null;
                    $changeDue = 0.0;
                    $balanceDue = round($total - $amountPaid, 2);
                }
            }

            $soldAt = today();

            $sale = Sale::create([
                'code' => Sale::nextCode(),
                'sale_type' => $validated['sale_type'],
                'job_order_id' => $jobOrder?->id,
                'payment_method' => $validated['payment_method'],
                'gross' => $totals['gross'],
                'labor_cost' => $laborCost,
                'total' => $total,
                'amount_tendered' => $tendered,
                'change_due' => $changeDue,
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue,
                'is_paid' => $isPaid,
                'sold_at' => $soldAt,
                'warranty_expires_at' => Carbon::parse($soldAt)->addMonthsNoOverflow(Sale::WARRANTY_MONTHS),
                'recorded_by' => Auth::id(),
            ]);

            foreach ($resolved as [$product, $qty, $lineTotal]) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                if ($product->stock > 0) {
                    // save() (not decrement()) so the Product "saving" hook recomputes availability.
                    $product->stock = max(0, $product->stock - $qty);
                    $product->save();
                }
            }

            if ($jobOrder) {
                // Recompute from every settlement recorded against this job order (this
                // one included), so a follow-up partial payment moves it to "paid" once
                // the running total reaches the job cost.
                $jobOrder->syncPaymentStatus();

                // The approved parts shown in this job order's cart lines are being sold
                // in this same transaction — settle them, the same way a product line
                // leaves the till the moment it's rung up.
                if (! empty($validated['part_out_ids'])) {
                    $billedPartOuts = PartOut::whereIn('id', $validated['part_out_ids'])
                        ->where('job_order_id', $jobOrder->id)
                        ->where('status', 'pending_billing')
                        ->get(['id', 'code']);

                    if ($billedPartOuts->isNotEmpty()) {
                        PartOut::whereIn('id', $billedPartOuts->pluck('id'))->update(['status' => 'billed']);
                    }
                }
            }

            return $sale;
        });

        $itemCount = count($lineItems);
        ActivityLog::record([
            'module' => 'point_of_sale',
            'action' => 'sale_recorded',
            'reference' => $sale->code,
            'title' => $isJobOrder
                ? "Job order {$sale->jobOrder->code} billed"
                : $itemCount.' '.\Illuminate\Support\Str::plural('item', $itemCount).' sold',
            'detail' => $sale->payment_method.($sale->is_paid ? '' : ' · partial payment'),
            'after_value' => 'PHP '.number_format((float) $sale->total, 2),
        ]);

        foreach ($billedPartOuts as $billedPartOut) {
            ActivityLog::record([
                'module' => 'parts_out',
                'action' => 'billed',
                'reference' => $billedPartOut->code,
                'title' => "Parts Out {$billedPartOut->code} marked as billed",
                'detail' => "Settled via sale {$sale->code}",
            ]);
        }

        if (! $sale->is_paid) {
            $note = ' Paid ₱'.number_format((float) $sale->amount_paid, 2)
                .' of ₱'.number_format((float) $sale->total, 2)
                .' — balance ₱'.number_format((float) $sale->balance_due, 2).'.';
        } elseif ($sale->amount_tendered !== null) {
            $note = ' Change: ₱'.number_format((float) $sale->change_due, 2).'.';
        } else {
            $note = '';
        }

        return redirect()->route('products.index', ['tab' => 'transaction'])
            ->with('status', "Sale {$sale->code} recorded — ₱".number_format((float) $sale->total, 2).'.'.$note);
    }

    public function exportDataset(Request $request, ForecastService $forecast)
    {
        $rows = $this->forecastDataset($forecast)['rows'];

        $filename = 'forecast-dataset-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['sales_ID', 'year', 'month', 'month_name', 'total_sales', 'product_count', 'avg_sales', 'total_revenue', 'job_count', 'avg_ticket']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['sales_id'], $row['year'], $row['month'], $row['month_name'],
                    $row['total_sales'], $row['product_count'], $row['avg_sales'],
                    $row['total_revenue'], $row['job_count'], $row['avg_ticket'],
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Server-authoritative receipt math: gross = product lines + labor = total.
     */
    private function computeTotals(float $itemsTotal, float $laborCost): array
    {
        $gross = round($itemsTotal + $laborCost, 2);

        return [
            'gross' => $gross,
            'total' => $gross,
        ];
    }

    /**
     * Monthly sales_summary / revenue_summary the forecasting model trains on, plus a
     * short SARIMA revenue projection. Window: last 13 closed months + the live month
     * + 3 forecast months, matching the cashier-page "Forecast dataset" panel.
     */
    private function forecastDataset(ForecastService $forecast): array
    {
        $history = 13;
        $ahead = 3;
        $firstMonth = Carbon::now()->startOfMonth()->subMonths($history);
        $currentMonth = Carbon::now()->startOfMonth();

        $saleAgg = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.sold_at', '>=', $firstMonth->toDateString())
            ->selectRaw('YEAR(sales.sold_at) y, MONTH(sales.sold_at) m, SUM(sale_items.quantity) units, SUM(sale_items.line_total) revenue, COUNT(DISTINCT sales.id) txns')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($r) => $r->y.'-'.$r->m);

        $jobAgg = DB::table('job_orders')
            ->whereNotNull('planned_start_date')
            ->whereDate('planned_start_date', '>=', $firstMonth->toDateString())
            ->selectRaw('YEAR(planned_start_date) y, MONTH(planned_start_date) m, COUNT(*) jobs, SUM(cost) job_revenue')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($r) => $r->y.'-'.$r->m);

        $revenueForecast = $forecast->compute(95, $ahead, 'revenue');
        $forecastByLabel = $revenueForecast['hasData']
            ? collect($revenueForecast['forecast'])->keyBy('label')
            : collect();

        $rows = [];
        $chart = [];
        $salesId = 1;
        for ($i = -$history; $i <= $ahead; $i++) {
            $month = $currentMonth->copy()->addMonths($i);
            $key = $month->year.'-'.$month->month;
            $isFuture = $month->greaterThan($currentMonth);
            $isLive = $month->equalTo($currentMonth);

            $sale = $saleAgg->get($key);
            $job = $jobAgg->get($key);

            $units = (int) ($sale->units ?? 0);
            $revenue = (float) ($sale->revenue ?? 0);
            $txns = (int) ($sale->txns ?? 0);
            $jobs = (int) ($job->jobs ?? 0);
            $jobRevenue = (float) ($job->job_revenue ?? 0);

            $forecastRevenue = (float) ($forecastByLabel[$month->format('M Y')]['value'] ?? 0);

            if (! $isFuture) {
                $rows[] = [
                    'sales_id' => $salesId++,
                    'year' => $month->year,
                    'month' => $month->month,
                    'month_name' => $month->format('F'),
                    'total_sales' => round($revenue, 2),
                    'product_count' => $units,
                    'avg_sales' => $txns > 0 ? round($revenue / $txns, 2) : 0,
                    'total_revenue' => round($revenue + $jobRevenue, 2),
                    'job_count' => $jobs,
                    'avg_ticket' => $jobs > 0 ? round($jobRevenue / $jobs, 2) : 0,
                ];
            }

            $chartValue = $isFuture ? $forecastRevenue : $revenue;
            $chart[] = [
                'label' => $month->format('M y'),
                'value' => round($chartValue, 2),
                'display' => '₱'.number_format($chartValue),
                'tone' => $isFuture ? 'forecast' : ($isLive ? 'live' : 'actual'),
            ];
        }

        $thisMonthRow = collect($rows)->firstWhere(fn ($r) => $r['year'] === $currentMonth->year && $r['month'] === $currentMonth->month);
        $nextMonthRevenue = (float) ($forecastByLabel->first()['value'] ?? 0);

        return [
            'rows' => array_reverse($rows),
            'chart' => $chart,
            'totalSalesThisMonth' => (float) ($thisMonthRow['total_sales'] ?? 0),
            'productCountThisMonth' => (int) ($thisMonthRow['product_count'] ?? 0),
            'totalRevenueThisMonth' => (float) ($thisMonthRow['total_revenue'] ?? 0),
            'nextMonthRevenue' => $nextMonthRevenue,
            'modelOrder' => $revenueForecast['hasData'] ? $revenueForecast['modelOrder'] : null,
        ];
    }
}
