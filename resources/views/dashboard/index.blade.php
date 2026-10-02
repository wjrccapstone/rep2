@php
    $topServices = \App\Models\JobOrder::query()
        ->join('services', 'services.id', '=', 'job_orders.service_id')
        ->selectRaw('services.name as service, count(*) as c')
        ->groupBy('services.name')
        ->orderByDesc('c')
        ->limit(2)
        ->get();
    $topServiceMax = max(1, $topServices->max('c') ?? 1);
    $peakVal = max(1, $peakPoint['value'] ?? 1);

    // Job-Order demand view: bars are shaded by magnitude (darkest = highest demand)
    // using the 4-shade blue palette, via bar-chart's per-point tone override.
    $demandMax = max(1, $demand->max('value'));
    $demandWithTone = $demand->map(function ($d) use ($demandMax) {
        $ratio = $d['value'] / $demandMax;
        $tone = $ratio >= 0.75 ? 'navyblue' : ($ratio >= 0.5 ? 'blue' : ($ratio >= 0.25 ? 'skyblue' : 'paleblue'));

        return [...$d, 'tone' => $tone];
    });
@endphp
<x-app-layout title="Dashboard" dark>
    <x-page-header title="Dashboard" subtitle="Welcome back! Here's your business overview." dark>
               <x-slot:actions>
            <div class="bb-actions">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2" id="dashboard-period-form">
                    {{-- Keeps the bottom demand-chart's own View/Range filters intact when only Period changes. --}}
                    <input type="hidden" name="view" value="{{ $metric }}">
                    <input type="hidden" name="range" value="{{ $rangeMonths }}">
                    <select name="period" onchange="document.getElementById('dashboard-period-form').submit()"
                        class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-white/30">
                        <option class="text-gray-900" value="all_time" @selected($period === 'all_time')>All Time</option>
                        <option class="text-gray-900" value="this_month" @selected($period === 'this_month')>This Month</option>
                        <option class="text-gray-900" value="last_month" @selected($period === 'last_month')>Last Month</option>
                        <option class="text-gray-900" value="this_year" @selected($period === 'this_year')>This Year</option>
                        <option class="text-gray-900" value="custom" @selected($period === 'custom')>Custom…</option>
                    </select>
                    @if ($period === 'custom')
                        <select name="year" onchange="document.getElementById('dashboard-period-form').submit()"
                            class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-white/30">
                            @foreach ($periodYearOptions as $y)
                                <option class="text-gray-900" value="{{ $y }}" @selected($customYear === $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                        <select name="month" onchange="document.getElementById('dashboard-period-form').submit()"
                            class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-white/30">
                            @foreach (range(1, 12) as $m)
                                <option class="text-gray-900" value="{{ $m }}" @selected($customMonth === $m)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                    @endif
                </form>

                @include('partials.bulletin-board')
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total Revenue" value="PHP {{ number_format($totalRevenue, 2) }}" icon="peso" tone="navy" />
        <x-stat-card label="Active Jobs" value="{{ $activeJobs }}" icon="briefcase" tone="brand" />
        <x-stat-card label="Pending Jobs" value="{{ $pendingJobs }}" icon="clock" tone="amber" />
        <x-stat-card label="Total Clients" value="{{ $totalClients }}" icon="users" tone="teal" />
    </div>

    <div class="mt-6">
        <div class="rounded-2xl bg-white p-5 shadow-lg shadow-black/10">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Job Order Status Breakdown</p>
                    <p class="mt-0.5 text-xs text-slate-400">Where all {{ $totalJobOrders }} job orders currently sit in the pipeline</p>

                    <div class="mt-5">
                        @if ($totalJobOrders === 0)
                            <p class="text-sm text-slate-400">No job orders yet.</p>
                        @else
                            <x-status-pie-chart :segments="$jobStatusBreakdown" :total="$totalJobOrders" />
                        @endif
                    </div>
                </div>

                <div class="flex h-full flex-col lg:border-l lg:border-slate-100 lg:pl-8">
                    <p class="text-sm font-semibold text-slate-700">Payment Status Breakdown</p>
                    <p class="mt-0.5 text-xs text-slate-400">How much of PHP {{ number_format($totalBilled, 0) }} billed has actually been collected</p>

                    <div class="mt-5 flex flex-1 flex-col justify-center">
                        @if ($totalBilled <= 0)
                            <p class="text-sm text-slate-400">No billed job orders yet.</p>
                        @else
                            <x-payment-status-bar :segments="$paymentBreakdown" :total="$totalBilled" :collected-percent="$collectedPercent" />
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="rounded-2xl bg-white p-5 shadow-lg shadow-black/10">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between" id="dashboard-filters">
                {{-- Keeps the header's Period filter intact when only View/Range changes. --}}
                <input type="hidden" name="period" value="{{ $period }}">
                @if ($period === 'custom')
                    <input type="hidden" name="year" value="{{ $customYear }}">
                    <input type="hidden" name="month" value="{{ $customMonth }}">
                @endif
                <div>
                    <label for="view" class="text-xs font-medium text-slate-400">View</label>
                    <select id="view" name="view" onchange="document.getElementById('dashboard-filters').submit()"
                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-64">
                        <option value="demand" @selected($metric === 'demand')>Job-Order demand</option>
                        <option value="revenue" @selected($metric === 'revenue')>Sales Revenue</option>
                    </select>
                </div>
                <div>
                    <label for="range" class="text-xs font-medium text-slate-400">Range</label>
                    <select id="range" name="range" onchange="document.getElementById('dashboard-filters').submit()"
                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-40">
                        <option value="6" @selected($rangeMonths === 6)>Last 6 months</option>
                        <option value="12" @selected($rangeMonths === 12)>Last 12 months</option>
                    </select>
                </div>
                <a href="{{ route('forecasting.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#A8BFA3]/15 px-3 py-2.5 text-xs font-medium text-[#7A9E7E] hover:bg-[#A8BFA3]/25">
                    View forecast
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </a>
            </form>

            <div class="mt-6">
                <x-bar-chart :data="$metric === 'revenue' ? $demand : $demandWithTone" :color="$metric === 'revenue' ? 'seafoam' : 'blue'" fill />
            </div>

            <div class="mt-6 space-y-3 border-t border-slate-100 pt-5">
                <p class="text-xs font-medium text-slate-400">Peak &amp; Trough</p>
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs font-medium text-slate-600">
                        <span>Peak ({{ $peakPoint['label'] ?? '—' }})</span>
                        <span class="text-slate-400">{{ $peakPoint['display'] ?? '—' }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#2E5AA8]" style="width: 100%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs font-medium text-slate-600">
                        <span>Trough ({{ $troughPoint['label'] ?? '—' }})</span>
                        <span class="text-slate-400">{{ $troughPoint['display'] ?? '—' }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#BBDEFB]" style="width: {{ max(4, round((($troughPoint['value'] ?? 0) / $peakVal) * 100)) }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-3 border-t border-slate-100 pt-5">
                <p class="text-xs font-medium text-slate-400">Top Services (all time)</p>
                @forelse ($topServices as $service)
                    @php $pct = max(4, round(($service->c / $topServiceMax) * 100)); @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs font-medium text-slate-600">
                            <span>{{ $service->service }}</span>
                            <span class="text-slate-400">{{ $service->c }} orders</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-[#1E88E5]" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No job orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
