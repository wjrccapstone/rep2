<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        [$period, $customYear, $customMonth, $rangeStart, $rangeEnd] = $this->resolvePeriod($request);

        // Scopes the KPI tiles, the status donut, and the payment breakdown to the
        // selected Period — the demand/revenue trend chart below keeps its own
        // separate Range control (a trailing window, not a single period) untouched.
        $periodScope = fn ($query) => $rangeStart
            ? $query->whereBetween('planned_start_date', [$rangeStart, $rangeEnd])
            : $query;

        // Total Revenue: fully-paid orders' cost, plus — since job orders here are
        // non-refundable — whatever was actually collected (not the full sticker
        // cost) on orders that got Cancelled before ever being marked Paid. That
        // deposit is real, kept revenue; it shouldn't sit invisibly as "Partial"
        // forever just because the job itself never finished.
        $paidAmount = (float) $periodScope(JobOrder::where('payment_status', 'paid'))->sum('cost');
        $paidCount = $periodScope(JobOrder::where('payment_status', 'paid'))->count();

        $cancelledKeptAmount = (float) $periodScope(
            JobOrder::where('status', 'cancelled')->where('payment_status', '!=', 'paid')
        )->withSum('sales as paid_sum', 'amount_paid')->get()->sum('paid_sum');
        $cancelledKeptCount = $periodScope(
            JobOrder::where('status', 'cancelled')->where('payment_status', '!=', 'paid')
        )->whereHas('sales')->count();

        $totalRevenue = $paidAmount + $cancelledKeptAmount;

        $activeJobs = $periodScope(JobOrder::whereIn('status', ['pending', 'in_progress', 'for_pickup']))->count();
        $pendingJobs = $periodScope(JobOrder::where('status', 'pending'))->count();
        // Total Clients is a running total, not a per-period figure — always all-time.
        $totalClients = Customer::count();

        // Job Order Status Breakdown: full pipeline (intake -> in progress -> ready for
        // pickup -> completed), with cancelled as the terminal exit state — same tones
        // JobOrder::statusTone() already uses for badges elsewhere, so the color coding
        // reads the same everywhere in the app.
        $statusCounts = $periodScope(JobOrder::query())->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $totalJobOrders = (int) $statusCounts->sum();
        $jobStatusBreakdown = collect([
            ['key' => 'pending', 'label' => 'Pending', 'tone' => 'amber'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'tone' => 'brand'],
            ['key' => 'for_pickup', 'label' => 'For Pick-up', 'tone' => 'indigo'],
            ['key' => 'completed', 'label' => 'Completed', 'tone' => 'teal'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'tone' => 'rose'],
        ])->map(fn ($s) => [...$s, 'value' => (int) ($statusCounts[$s['key']] ?? 0)]);

        // Payment Status Breakdown: same "kept deposit counts as collected" logic as
        // Total Revenue above — Cancelled orders move out of "Partial" (they're no
        // longer at risk of anything; the case is closed) and their actual collected
        // amount joins "Paid". What's left under "Partial" is only still-active work
        // that could still finish or still get cancelled — genuinely outstanding.
        $activePartialAmount = (float) $periodScope(
            JobOrder::where('payment_status', 'partial')->where('status', '!=', 'cancelled')
        )->sum('cost');
        $activePartialCount = $periodScope(
            JobOrder::where('payment_status', 'partial')->where('status', '!=', 'cancelled')
        )->count();

        $paymentBreakdown = collect([
            ['key' => 'paid', 'label' => 'Paid', 'tone' => 'teal', 'amount' => $paidAmount + $cancelledKeptAmount, 'count' => $paidCount + $cancelledKeptCount],
            ['key' => 'partial', 'label' => 'Partial', 'tone' => 'amber', 'amount' => $activePartialAmount, 'count' => $activePartialCount],
        ]);
        $totalBilled = $paymentBreakdown->sum('amount');
        $collectedPercent = $totalBilled > 0
            ? round((($paymentBreakdown->firstWhere('key', 'paid')['amount'] ?? 0) / $totalBilled) * 100, 1)
            : null;

        $metric = $request->query('view', 'demand') === 'revenue' ? 'revenue' : 'demand';
        $rangeMonths = (int) $request->query('range', 6);
        $rangeMonths = in_array($rangeMonths, [6, 12], true) ? $rangeMonths : 6;

        $months = collect(range($rangeMonths - 1, 0))->map(fn ($i) => Carbon::now()->startOfMonth()->subMonths($i));

        $demand = $months->map(function (Carbon $month) use ($metric) {
            $query = JobOrder::whereYear('planned_start_date', $month->year)
                ->whereMonth('planned_start_date', $month->month);

            if ($metric === 'revenue') {
                $value = (float) $query->sum('cost');

                return ['label' => $month->format('M'), 'value' => $value, 'display' => 'PHP '.number_format($value)];
            }

            $value = $query->count();

            return ['label' => $month->format('M'), 'value' => $value, 'display' => $value.' orders'];
        })->values();

        $peakPoint = $demand->sortByDesc('value')->first();
        $troughPoint = $demand->sortBy('value')->first();

        $minYear = (int) (JobOrder::min('planned_start_date')
            ? Carbon::parse(JobOrder::min('planned_start_date'))->year
            : now()->year);
        $periodYearOptions = range(max($minYear, now()->year), $minYear);

        return view('dashboard.index', [
            'totalRevenue' => $totalRevenue,
            'activeJobs' => $activeJobs,
            'pendingJobs' => $pendingJobs,
            'totalClients' => $totalClients,
            'demand' => $demand,
            'metric' => $metric,
            'rangeMonths' => $rangeMonths,
            'peakPoint' => $peakPoint,
            'troughPoint' => $troughPoint,
            'jobStatusBreakdown' => $jobStatusBreakdown,
            'totalJobOrders' => $totalJobOrders,
            'paymentBreakdown' => $paymentBreakdown,
            'totalBilled' => $totalBilled,
            'collectedPercent' => $collectedPercent,
            'period' => $period,
            'customYear' => $customYear,
            'customMonth' => $customMonth,
            'periodYearOptions' => $periodYearOptions,
        ]);
    }

    /**
     * Resolves the Period filter (This Month / Last Month / This Year / All Time /
     * Custom) into a concrete date range for scoping planned_start_date. Defaults to
     * All Time so a first-time visit still shows the familiar, unfiltered totals.
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', 'all_time');
        if (! in_array($period, ['this_month', 'last_month', 'this_year', 'all_time', 'custom'], true)) {
            $period = 'all_time';
        }

        $customYear = (int) $request->query('year', now()->year);
        $customMonth = (int) $request->query('month', now()->month);
        $customMonth = $customMonth >= 1 && $customMonth <= 12 ? $customMonth : now()->month;

        [$start, $end] = match ($period) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::create($customYear, $customMonth, 1)->startOfMonth(),
                Carbon::create($customYear, $customMonth, 1)->endOfMonth(),
            ],
            default => [null, null],
        };

        return [$period, $customYear, $customMonth, $start, $end];
    }
}
