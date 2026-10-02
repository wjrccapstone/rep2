<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\PartOut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ForecastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, ForecastService $forecast)
    {
        $role = Auth::user()?->role;

        // Technicians get a read-only Catalog (to check stock/availability before
        // requesting parts), Parts Out (to submit requests), and Archive (to browse
        // retired stock). Parts Out is a technician/admin tool — technicians submit
        // requests, admins approve/bill them — so cashiers don't get it either.
        $allowedTabs = match ($role) {
            'technician' => ['catalog', 'parts-out', 'archive'],
            'cashier' => ['catalog', 'transaction', 'sales', 'archive'],
            default => ['catalog', 'transaction', 'parts-out', 'sales', 'archive'],
        };

        $tab = $request->query('tab');
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = match ($role) {
                'cashier' => 'transaction',
                'technician' => 'parts-out',
                default => 'catalog',
            };
        }

        $products = collect();
        $sales = collect();
        $saleStats = null;
        $pos = null;
        $partsOut = null;

        if ($tab === 'transaction') {
            $pos = app(CashierController::class)->transactionData($request, $forecast);
        } elseif ($tab === 'parts-out') {
            $partsOut = app(PartOutController::class)->data($request);
        } elseif ($tab === 'sales') {
            $query = Sale::with('items')->withCount('items');

            if ($search = $request->query('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', "%{$search}%"));
                });
            }

            $warranty = $request->query('warranty');
            if ($warranty === 'active') {
                $query->whereDate('warranty_expires_at', '>=', today());
            } elseif ($warranty === 'expired') {
                $query->whereDate('warranty_expires_at', '<', today());
            }

            $sales = $query->orderByDesc('sold_at')->orderByDesc('id')->paginate(15)->withQueryString();

            $saleStats = [
                'total' => Sale::count(),
                'revenue' => Sale::sum('total'),
                'warrantyActive' => Sale::whereDate('warranty_expires_at', '>=', today())->count(),
                'warrantyExpired' => Sale::whereDate('warranty_expires_at', '<', today())->count(),
            ];
        } else {
            $query = Product::query()->with('category')->when(
                $tab === 'archive',
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at'),
            );

            if ($search = $request->query('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($category = $request->query('category')) {
                $query->whereHas('category', fn ($c) => $c->where('name', $category));
            }

            $products = $query->orderBy('name')->paginate(15)->withQueryString();
        }

        return view('products.index', [
            'tab' => $tab,
            'allowedTabs' => $allowedTabs,
            'products' => $products,
            'sales' => $sales,
            'saleStats' => $saleStats,
            'pos' => $pos,
            'partsOut' => $partsOut,
            'tabCounts' => [
                'catalog' => Product::whereNull('archived_at')->count(),
                'transaction' => 0,
                'parts-out' => PartOut::count(),
                'sales' => Sale::count(),
                'archive' => Product::whereNotNull('archived_at')->count(),
            ],
            'filters' => [
                'q' => $request->query('q', ''),
                'category' => $request->query('category', ''),
                'warranty' => $request->query('warranty', ''),
                'range' => $request->query('range', ''),
                'technician' => $request->query('technician', ''),
            ],
            'nextCode' => Product::nextCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $categoryId = Category::where('name', $validated['category'])->value('id');

        $product = Product::create([
            ...Arr::except($validated, ['category']),
            'category_id' => $categoryId,
            'code' => Product::nextCode(),
        ]);

        if ($request->hasFile('image')) {
            $product->update(['image_path' => $request->file('image')->store('products', 'public')]);
        }

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'added',
            'reference' => $product->code,
            'title' => $product->name,
            'detail' => 'Category: '.$product->category->name,
        ]);

        return redirect()->route('products.index')->with('status', "Product {$product->code} added.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request);
        $categoryId = Category::where('name', $validated['category'])->value('id');
        $before = $product->only(['price', 'stock', 'availability', 'status']);

        $product->update([
            ...Arr::except($validated, ['category']),
            'category_id' => $categoryId,
        ]);

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->update(['image_path' => null]);
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->update(['image_path' => $request->file('image')->store('products', 'public')]);
        }

        $this->logProductChange($product, $before);

        return redirect()->route('products.index')->with('status', "Product {$product->code} updated.");
    }

    public function archive(Product $product): RedirectResponse
    {
        $product->update(['archived_at' => now()]);

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'archived',
            'reference' => $product->code,
            'title' => "{$product->name} moved to Archive",
        ]);

        return redirect()->route('products.index')->with('status', "Product {$product->code} archived.");
    }

    public function restore(Product $product): RedirectResponse
    {
        $product->update(['archived_at' => null]);

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'restored',
            'reference' => $product->code,
            'title' => "{$product->name} restored to catalog",
        ]);

        return redirect()->route('products.index', ['tab' => 'archive'])->with('status', "Product {$product->code} restored to catalog.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $code = $product->code;
        $name = $product->name;

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'deleted',
            'reference' => $code,
            'title' => "{$name} permanently deleted",
        ]);

        return redirect()->route('products.index', ['tab' => 'archive'])->with('status', "Product {$code} permanently deleted.");
    }

    /**
     * Empties the Archive in one action instead of deleting each product one by one.
     */
    public function destroyAllArchived(): RedirectResponse
    {
        $products = Product::whereNotNull('archived_at')->get();

        if ($products->isEmpty()) {
            return redirect()->route('products.index', ['tab' => 'archive'])->with('status', 'Archive is already empty.');
        }

        $count = $products->count();
        $codes = $products->pluck('code')->implode(', ');

        foreach ($products as $product) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();
        }

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'deleted',
            'reference' => 'ARCHIVE_ALL',
            'title' => "{$count} archived ".Str::plural('product', $count)." permanently deleted",
            'detail' => $codes,
        ]);

        return redirect()->route('products.index', ['tab' => 'archive'])->with('status', "{$count} archived ".Str::plural('product', $count)." permanently deleted.");
    }

    /**
     * Log the specific thing that changed on a product edit — a price change and a
     * stock adjustment are different, useful facts, not one generic "updated" row.
     */
    private function logProductChange(Product $product, array $before): void
    {
        $logged = false;
        $changes = $this->productChanges($before, $product);

        if ((float) $before['price'] !== (float) $product->price) {
            ActivityLog::record([
                'module' => 'product_inventory',
                'action' => 'price_changed',
                'reference' => $product->code,
                'title' => $product->name,
                'before_value' => 'PHP '.number_format((float) $before['price'], 2),
                'after_value' => 'PHP '.number_format((float) $product->price, 2),
                'field_changes' => $changes,
            ]);
            $logged = true;
        }

        if ((int) $before['stock'] !== (int) $product->stock) {
            $detail = $before['availability'] !== $product->availability
                ? 'Availability moved to '.(Product::AVAILABILITIES[$product->availability] ?? $product->availability)
                : null;

            ActivityLog::record([
                'module' => 'product_inventory',
                'action' => 'stock_adjusted',
                'reference' => $product->code,
                'title' => $product->name,
                'detail' => $detail,
                'before_value' => $before['stock'].' pcs',
                'after_value' => $product->stock.' pcs',
                'field_changes' => $changes,
            ]);
            $logged = true;
        }

        if (! $logged) {
            ActivityLog::record([
                'module' => 'product_inventory',
                'action' => 'updated',
                'reference' => $product->code,
                'title' => "{$product->name} details updated",
                'field_changes' => $changes,
            ]);
        }
    }

    /**
     * The full before/after snapshot shown in the activity log's detail modal — every
     * tracked field, not just the one that triggered the log row, so an admin can see
     * at a glance that (say) stock and status held steady while price moved.
     */
    private function productChanges(array $before, Product $product): array
    {
        $availabilityBefore = Product::AVAILABILITIES[$before['availability']] ?? ucfirst($before['availability']);

        return [
            [
                'label' => 'Selling price',
                'before' => 'PHP '.number_format((float) $before['price'], 2),
                'after' => 'PHP '.number_format((float) $product->price, 2),
                'changed' => (float) $before['price'] !== (float) $product->price,
            ],
            [
                'label' => 'Stock',
                'before' => (string) $before['stock'],
                'after' => (string) $product->stock,
                'changed' => (int) $before['stock'] !== (int) $product->stock,
            ],
            [
                'label' => 'Availability',
                'before' => $availabilityBefore,
                'after' => $product->availabilityLabel(),
                'changed' => $before['availability'] !== $product->availability,
            ],
            [
                'label' => 'Status',
                'before' => ucfirst($before['status']),
                'after' => ucfirst($product->status),
                'changed' => $before['status'] !== $product->status,
            ],
        ];
    }

    public function exportCsv(Request $request)
    {
        $tab = $request->query('tab') === 'archive' ? 'archive' : 'catalog';

        $products = Product::query()
            ->with('category')
            ->when($tab === 'archive', fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'))
            ->orderBy('name')
            ->get();

        $filename = ($tab === 'archive' ? 'product-archive' : 'product-catalog').'-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['code', 'name', 'category', 'price', 'stock', 'on_order', 'availability', 'status']);
            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->code,
                    $product->name,
                    $product->category->name,
                    $product->price,
                    $product->stock,
                    $product->on_order ? '1' : '0',
                    $product->availability,
                    $product->status,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($col) => strtolower(trim($col)), fgetcsv($handle) ?: []);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (! $data || empty($data['name']) || empty($data['category'])) {
                $skipped++;

                continue;
            }

            $categoryId = Category::where('name', $data['category'])->value('id');

            if (! $categoryId) {
                $skipped++;

                continue;
            }

            $status = in_array($data['status'] ?? null, Product::STATUSES, true) ? $data['status'] : 'active';

            // Availability is derived from stock by the Product model, not imported.
            $onOrder = in_array(strtolower(trim($data['on_order'] ?? '')), ['1', 'true', 'yes', 'on_order'], true);

            $attributes = [
                'name' => $data['name'],
                'category_id' => $categoryId,
                'price' => is_numeric($data['price'] ?? null) ? $data['price'] : 0,
                'stock' => is_numeric($data['stock'] ?? null) ? (int) $data['stock'] : 0,
                'on_order' => $onOrder,
                'status' => $status,
            ];

            $code = trim($data['code'] ?? '');
            $existing = $code !== '' ? Product::where('code', $code)->first() : null;

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                Product::create([...$attributes, 'code' => Product::nextCode()]);
                $created++;
            }
        }

        fclose($handle);

        ActivityLog::record([
            'module' => 'product_inventory',
            'action' => 'imported',
            'title' => 'Bulk CSV import',
            'detail' => "{$created} added, {$updated} updated, {$skipped} skipped",
        ]);

        return redirect()->route('products.index')
            ->with('status', "Import complete: {$created} added, {$updated} updated, {$skipped} skipped.");
    }

    public function exportSalesCsv(Request $request)
    {
        $sales = Sale::with('items')->orderByDesc('sold_at')->get();

        $filename = 'sales-records-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['code', 'date', 'product_name', 'quantity', 'unit_price', 'payment_method', 'warranty_expires_at']);
            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    fputcsv($handle, [
                        $sale->code,
                        $sale->sold_at->format('Y-m-d'),
                        $item->product_name,
                        $item->quantity,
                        $item->unit_price,
                        $sale->payment_method,
                        $sale->warranty_expires_at->format('Y-m-d'),
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

    public function importSalesCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($col) => strtolower(trim($col)), fgetcsv($handle) ?: []);

        $grouped = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (! $data || empty($data['product_name']) || empty($data['date'])) {
                continue;
            }
            $code = trim($data['code'] ?? '') ?: uniqid('row_');
            $grouped[$code][] = $data;
        }
        fclose($handle);

        $created = 0;
        $skipped = 0;

        foreach ($grouped as $rows) {
            $first = $rows[0];
            $paymentMethod = in_array($first['payment_method'] ?? null, Sale::PAYMENT_METHODS, true) ? $first['payment_method'] : 'Cash';
            $soldAt = $first['date'];

            $sale = Sale::create([
                'code' => Sale::nextCode(),
                'payment_method' => $paymentMethod,
                'total' => 0,
                'sold_at' => $soldAt,
                'warranty_expires_at' => \Illuminate\Support\Carbon::parse($soldAt)->addMonthsNoOverflow(Sale::WARRANTY_MONTHS),
            ]);

            $total = 0;
            foreach ($rows as $data) {
                $qty = is_numeric($data['quantity'] ?? null) ? (int) $data['quantity'] : 1;
                $unitPrice = is_numeric($data['unit_price'] ?? null) ? (float) $data['unit_price'] : 0;
                $lineTotal = $qty * $unitPrice;
                $total += $lineTotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => Product::where('name', $data['product_name'])->value('id'),
                    'product_name' => $data['product_name'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $sale->update(['total' => $total]);
            $created++;
        }

        return redirect()->route('products.index', ['tab' => 'sales'])
            ->with('status', "Import complete: {$created} sales added, {$skipped} rows skipped.");
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Category::pluck('name'))],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'on_order' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Product::STATUSES)],
        ]);

        // Availability is derived from stock by the Product model; never set by hand.
        $data['on_order'] = $request->boolean('on_order');

        return $data;
    }
}
