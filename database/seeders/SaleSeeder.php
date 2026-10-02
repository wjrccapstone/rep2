<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SaleSeeder extends Seeder
{
    /**
     * Seeds 72 months (6 years) of product-sales history so the Forecasting
     * page's Product Sales tab has enough data for a seasonal SARIMA fit.
     *
     * Each month's total units sold is driven directly by trend + seasonality
     * (plus one small noise term), then split into individual sales/line items
     * that sum exactly to that target. This keeps the noise-to-signal ratio
     * low so the seasonal pattern stays learnable, unlike compounding random
     * noise independently at the sale-count, line-item-count, and quantity
     * level (which drowns the signal in unpredictable variance).
     */
    public function run(): void
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->call(ProductSeeder::class);
            $products = Product::all();
        }
        $cheapProducts = $products->filter(fn ($p) => $p->price < 10000)->values();
        if ($cheapProducts->isEmpty()) {
            $cheapProducts = $products;
        }

        $userIds = User::pluck('id');
        $recordedBy = $userIds->isNotEmpty() ? fn () => $userIds->random() : fn () => null;

        Schema::disableForeignKeyConstraints();
        SaleItem::truncate();
        Sale::truncate();
        Schema::enableForeignKeyConstraints();

        // Seasonal demand multiplier per calendar month (Jan..Dec) — busier around
        // back-to-school (Jun/Jul) and year-end holiday shopping (Nov/Dec).
        $seasonality = [1 => 0.8, 2 => 0.85, 3 => 0.9, 4 => 0.95, 5 => 1.1, 6 => 1.3, 7 => 1.4, 8 => 1.15, 9 => 0.85, 10 => 0.9, 11 => 1.2, 12 => 1.35];

        $months = 72; // 6 years of history through current month — several full seasonal cycles for SARIMA
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $growthSlope = 0.12; // gentle upward trend in monthly unit volume over time
        $code = 1;

        for ($m = 0; $m < $months; $m++) {
            $monthStart = $start->copy()->addMonths($m);
            if ($monthStart->greaterThan(now()->startOfMonth())) {
                break;
            }

            $calMonth = (int) $monthStart->format('n');
            $baseUnits = 16 + ($m * $growthSlope);
            $targetUnits = max(4, (int) round($baseUnits * $seasonality[$calMonth]) + random_int(-1, 1));

            $remaining = $targetUnits;
            while ($remaining > 0) {
                $soldAt = $monthStart->copy()->addDays(random_int(0, $monthStart->daysInMonth - 1));
                if ($soldAt->greaterThan(now())) {
                    $soldAt = now()->copy();
                }

                $saleUnits = min($remaining, random_int(1, 4));
                $remaining -= $saleUnits;

                $sale = Sale::create([
                    'code' => sprintf('TXN-%03d', $code++),
                    'payment_method' => Sale::PAYMENT_METHODS[array_rand(Sale::PAYMENT_METHODS)],
                    'total' => 0,
                    'sold_at' => $soldAt,
                    'warranty_expires_at' => $soldAt->copy()->addMonthsNoOverflow(Sale::WARRANTY_MONTHS),
                    'recorded_by' => $recordedBy(),
                    'created_at' => $soldAt,
                    'updated_at' => $soldAt,
                ]);

                // Split this sale's units across 1-2 line items, summing exactly to $saleUnits.
                $itemCount = $saleUnits > 1 ? random_int(1, 2) : 1;
                $unitsLeft = $saleUnits;
                $total = 0;

                for ($k = 0; $k < $itemCount; $k++) {
                    $isLast = $k === $itemCount - 1;
                    $qty = $isLast ? $unitsLeft : random_int(1, $unitsLeft - ($itemCount - $k - 1));
                    $unitsLeft -= $qty;

                    $pool = $qty > 1 ? $cheapProducts : $products;
                    $product = $pool->random();
                    $lineTotal = $qty * $product->price;
                    $total += $lineTotal;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'line_total' => $lineTotal,
                        'created_at' => $soldAt,
                        'updated_at' => $soldAt,
                    ]);
                }

                $sale->update(['total' => $total]);
            }
        }
    }
}
