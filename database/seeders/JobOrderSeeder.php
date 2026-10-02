<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobOrderSeeder extends Seeder
{
    /**
     * Seeds 72 months (6 years) of job-order history against the existing
     * customers/technicians. Each month's per-order costs are drawn from a
     * per-service base price then rescaled so the month's total revenue
     * lands on a deterministic target tied to order count, so the Sales
     * Revenue forecast tracks the same seasonal/trend signal as order count
     * rather than drowning in random service-mix price noise.
     */
    public function run(): void
    {
        $customerIds = Customer::pluck('id');
        if ($customerIds->isEmpty()) {
            $this->command?->warn('No customers found — skipping JobOrderSeeder.');

            return;
        }

        $technicianUserIds = User::where('role', 'technician')->pluck('id');
        if ($technicianUserIds->isEmpty()) {
            $technicianUserIds = User::pluck('id');
        }
        $technicianIds = $technicianUserIds->map(
            fn ($userId) => Technician::firstOrCreate(['user_id' => $userId])->id
        );

        Schema::disableForeignKeyConstraints();
        JobOrder::truncate();
        DB::table('devices')->truncate();
        Schema::enableForeignKeyConstraints();

        $services = ['Package', 'Repair', 'Data Recovery', 'Hardware Upgrade', 'Maintenance', 'Diagnostics'];
        $serviceIds = collect($services)->mapWithKeys(
            fn ($name) => [$name => Service::firstOrCreate(['name' => $name])->id]
        );
        $devices = ['Dell Laptop', 'HP Laptop', 'Lenovo Laptop', 'MacBook', 'Desktop PC', 'Gaming PC', 'External HDD', 'Printer'];

        // Base price per service (PHP) — each order gets +/-15% noise on top of this.
        $serviceBaseCost = [
            'Package' => 3500,
            'Repair' => 1800,
            'Data Recovery' => 4200,
            'Hardware Upgrade' => 6000,
            'Maintenance' => 900,
            'Diagnostics' => 500,
        ];

        $avgServiceCost = array_sum($serviceBaseCost) / count($serviceBaseCost);

        // Seasonal demand multiplier per calendar month (Jan..Dec) — busier mid-year (school opening) and Nov/Dec (holidays).
        $seasonality = [1 => 0.8, 2 => 0.85, 3 => 0.9, 4 => 0.95, 5 => 1.05, 6 => 1.35, 7 => 1.4, 8 => 1.1, 9 => 0.85, 10 => 0.95, 11 => 1.25, 12 => 1.3];

        $months = 72; // 6 years of history through current month — several full seasonal cycles for SARIMA
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $growthSlope = 0.15; // gentle upward trend in order volume over time
        $code = 1;

        for ($m = 0; $m < $months; $m++) {
            $monthStart = $start->copy()->addMonths($m);
            if ($monthStart->greaterThan(now()->startOfMonth())) {
                break;
            }

            $calMonth = (int) $monthStart->format('n');
            $baseOrders = 6 + ($m * $growthSlope);
            $orderCount = max(2, (int) round($baseOrders * $seasonality[$calMonth]) + random_int(-1, 1));

            // Build the month's orders first (with a raw, noisy cost per order), then rescale
            // every cost so the month's total lands on a deterministic target tied to order
            // count. This keeps per-order price variety while removing the "which random mix
            // of cheap/expensive services got picked this month" noise from monthly revenue,
            // so Sales Revenue tracks the same learnable seasonal/trend signal as order count.
            $monthOrders = [];
            $rawCosts = [];

            for ($j = 0; $j < $orderCount; $j++) {
                $orderDate = $monthStart->copy()->addDays(random_int(0, $monthStart->daysInMonth - 1));
                $isCurrentMonth = $monthStart->isSameMonth(now());
                $isFuture = $orderDate->greaterThan(now());

                if ($isFuture) {
                    $status = 'pending';
                    $paymentStatus = 'unpaid';
                } elseif ($isCurrentMonth) {
                    $status = fake()->randomElement(['pending', 'pending', 'in_progress', 'in_progress', 'for_pickup', 'completed']);
                    $paymentStatus = $status === 'completed' ? 'paid' : fake()->randomElement(['unpaid', 'partial']);
                } else {
                    $status = fake()->randomElement(['completed', 'completed', 'completed', 'completed', 'cancelled']);
                    $paymentStatus = $status === 'cancelled' ? 'unpaid' : 'paid';
                }

                $due = $orderDate->copy()->addDays(random_int(5, 21));
                $service = $services[array_rand($services)];

                $monthOrders[] = [
                    'orderDate' => $orderDate,
                    'status' => $status,
                    'paymentStatus' => $paymentStatus,
                    'due' => $due,
                    'service' => $service,
                ];
                $rawCosts[] = $serviceBaseCost[$service] * (random_int(90, 110) / 100);
            }

            $rawTotal = array_sum($rawCosts) ?: 1;
            $targetTotal = $orderCount * $avgServiceCost;
            $scale = $targetTotal / $rawTotal;

            foreach ($monthOrders as $i => $order) {
                $cost = max(100, round(($rawCosts[$i] * $scale) / 50) * 50);

                $jobOrder = JobOrder::create([
                    'code' => sprintf('JON-%03d', $code++),
                    'customer_id' => $customerIds->random(),
                    'technician_id' => $technicianIds->random(),
                    'service_id' => $serviceIds[$order['service']],
                    'status' => $order['status'],
                    'payment_status' => $order['paymentStatus'],
                    'planned_start_date' => $order['orderDate'],
                    'due_date' => $order['due'],
                    'cost' => $cost,
                    'issue' => fake()->sentence(8),
                    'work_summary' => $order['status'] === 'completed' ? fake()->sentence(10) : null,
                    'created_at' => $order['orderDate'],
                    'updated_at' => $order['orderDate'],
                ]);

                $jobOrder->device()->create([
                    'device_model' => $devices[array_rand($devices)],
                ]);
            }
        }
    }
}
