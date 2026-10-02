@php
    $unitWord = match ($metric) {
        'revenue' => 'revenue',
        'product_sales' => 'units',
        default => 'orders',
    };

    $avgProjectedLabel = match ($metric) {
        'revenue' => 'Avg. Projected Revenue/Yr',
        'product_sales' => 'Avg. Projected Units/Yr',
        default => 'Avg. Projected Orders/Yr',
    };
    $avgProjectedValue = $metric === 'revenue' ? 'PHP '.number_format($result['avgProjectedPerYear']) : number_format($result['avgProjectedPerYear']);
    $viewLabel = match ($metric) {
        'revenue' => 'Sales Revenue',
        'product_sales' => 'Product Sales',
        default => 'Job-Order demand',
    };

    $pctTone = fn ($v) => $v === null ? 'text-slate-400' : ($v >= 0 ? 'text-teal-600' : 'text-rose-600');
    $pctLabel = fn ($v) => $v === null ? '—' : (($v >= 0 ? '↑ +' : '↓ ').number_format($v, 1).'%');

    $isRevenue = $metric === 'revenue';
    $isDaily = $isRevenue && $granularity === 'daily';

    $compact = function ($v) {
        $abs = abs($v);
        if ($abs >= 1_000_000) {
            return number_format($v / 1_000_000, 2).'M';
        }
        if ($abs >= 1_000) {
            return number_format($v / 1_000, 1).'K';
        }

        return number_format($v, 0);
    };

    // "Forecast range" dropdown labels, e.g. "2026 – 2032". Derived from the forecast's actual
    // month span (start month + N*12 - 1), not arithmetic on the start year — the forecast
    // begins mid-year, so a 6-year (72-month) horizon that starts Sep 2026 runs into 2032.
    // Kept consistent with the "View Details" subtitle for the same reason.
    $forecastStart = optional($result['forecastBand']->first())['date'] ?? null;
    $rangeLabel = function (int $years) use ($forecastStart) {
        if (! $forecastStart) {
            return '—';
        }
        $start = $forecastStart->year;
        $end = $forecastStart->copy()->addMonths($years * 12 - 1)->year;

        return $start === $end ? (string) $start : $start.' – '.$end;
    };
    $rangeOptions = ['1' => $rangeLabel(1), '3' => $rangeLabel(3), 'all' => $rangeLabel(6)];
@endphp
<x-app-layout title="Sales Forecasting">
    @if (! $result['hasData'])
        <x-coming-soon
            :title="$result['forecastPending'] ?? false ? 'Forecast is being prepared' : 'Not enough data yet'"
            :description="$result['forecastPending'] ?? false ? 'The Python forecasting service is processing the latest data. This page will refresh automatically.' : 'At least 24 monthly data points are required to fit the seasonal Python SARIMA model.'" />
    @else
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('forecasting.index', ['metric' => 'demand', 'confidence' => $result['confidence']]) }}"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $metric === 'demand' ? 'bg-teal-300 text-teal-950 shadow-sm' : 'text-slate-700 hover:text-slate-900' }}">
                    Job-Order
                </a>
                <a href="{{ route('forecasting.index', ['metric' => 'product_sales', 'confidence' => $result['confidence']]) }}"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $metric === 'product_sales' ? 'bg-teal-300 text-teal-950 shadow-sm' : 'text-slate-700 hover:text-slate-900' }}">
                    Product Sales
                </a>
                <a href="{{ route('forecasting.index', ['metric' => 'revenue', 'confidence' => $result['confidence']]) }}"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $metric === 'revenue' ? 'bg-teal-300 text-teal-950 shadow-sm' : 'text-slate-700 hover:text-slate-900' }}">
                    Sales Revenue
                </a>
            </div>

            <button type="button" onclick="openDescriptiveAnalysisModal()"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v-6.5m4.5 6.5v-10m4.5 10V13M4.5 19.5h15A1.5 1.5 0 0 0 21 18V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z" /></svg>
                Descriptive Analysis
            </button>
        </div>

        @if ($isRevenue && ! $isDaily)
            <form method="GET" action="{{ route('forecasting.index') }}" class="mb-5 flex flex-wrap items-end gap-4">
                <input type="hidden" name="metric" value="revenue">
                <input type="hidden" name="view" value="monthly">
                <div>
                    <label for="years" class="text-xs font-medium text-slate-400">Forecast range</label>
                    <select id="years" name="years" onchange="this.form.submit()"
                        class="mt-1 block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <option value="1" @selected($years === '1')>{{ $rangeOptions['1'] }}</option>
                        <option value="3" @selected($years === '3')>{{ $rangeOptions['3'] }}</option>
                        <option value="all" @selected($years === 'all')>{{ $rangeOptions['all'] }}</option>
                    </select>
                </div>
                <div>
                    <label for="confidence" class="text-xs font-medium text-slate-400">Confidence interval</label>
                    <select id="confidence" name="confidence" onchange="this.form.submit()"
                        class="mt-1 block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @foreach ([90, 95, 99] as $level)
                            <option value="{{ $level }}" @selected($result['confidence'] === $level)>{{ $level }}%</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" name="refresh_forecast" value="1"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7Z" /></svg>
                    Run Forecast
                </button>
            </form>
        @endif

        @if ($isDaily)
            @if (! $daily['hasData'])
                <x-coming-soon title="No recent daily revenue" description="Paid job orders from the last {{ $daily['days'] }} days will show up here." />
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Latest Day</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">PHP {{ $compact($daily['latestValue']) }}</p>
                        <p class="mt-1 text-xs font-medium {{ $pctTone($daily['dayOverDayPercent']) }}">
                            {{ $daily['latestLabel'] }} &middot; {{ $daily['dayOverDayPercent'] === null ? 'no prior day to compare' : $pctLabel($daily['dayOverDayPercent']).' vs. previous day' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">{{ $daily['days'] }}-Day Total</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">PHP {{ $compact($daily['total']) }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $daily['periodLabel'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Daily Average</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">PHP {{ $compact($daily['average']) }}</p>
                        <p class="mt-1 text-xs font-medium {{ $pctTone($daily['weekOverWeekPercent']) }}">
                            {{ $daily['weekOverWeekPercent'] === null ? 'Not enough history yet' : $pctLabel($daily['weekOverWeekPercent']).' vs. prior week' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Best Day of Week</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $daily['bestWeekday'] }}</p>
                        <p class="mt-1 text-xs text-slate-400">avg PHP {{ $compact($daily['bestWeekdayAvg']) }}/day</p>
                    </div>
                </div>
            @endif
        @elseif ($isRevenue)
            @php
                $accuracyScore = $result['accuracyAvailable'] ? max(0, min(100, round(100 - $result['mape']))) : null;
                $lastActualLabel = optional($result['history']->last())['label'];
            @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Forecast Period</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ $result['forecastPeriodLabel'] }}</p>
                    <div class="mt-2">
                        <x-sparkline :data="$result['forecast']->pluck('value')->all()" tone="teal" :width="96" :height="20" />
                    </div>
                    <p class="mt-2 text-xs text-slate-400">
                        {{ $result['forecast']->count() }} months forecast
                        @if ($lastActualLabel)
                            &middot; data through {{ $lastActualLabel }}
                        @endif
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Total Projected Revenue</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">PHP {{ $compact($result['forecastTotal']) }}</p>
                    <p class="mt-1 text-xs font-medium {{ $pctTone($result['baselineGrowthPercent']) }}">
                        {{ $result['baselineGrowthPercent'] === null ? 'No baseline yet' : $pctLabel($result['baselineGrowthPercent']).' vs. current annual pace' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Primary Driver (Seasonality)</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($result['seasonalStrength'] * 100, 0) }}% seasonal swing</p>
                    <p class="mt-1 text-xs text-slate-400">Peaks in {{ $result['peakMonth'] }} &middot; index {{ number_format($result['peakSeasonalIndex'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Model Performance</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">
                        {{ $result['accuracyAvailable'] ? 'MAPE '.number_format($result['mape'], 1).'%' : 'Backtest unavailable' }}
                    </p>
                    @if ($result['accuracyAvailable'])
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-teal-500" style="width: {{ $accuracyScore }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">Backtested on {{ $result['holdoutMonths'] }} held-out months</p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">Forecast generated; hold-out accuracy is not calculated for this Python snapshot.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Forecast Period</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ $result['forecastPeriodLabel'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">Predicted growth for next {{ $result['forecastYears'] }} years</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">{{ $avgProjectedLabel }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ $avgProjectedValue }}</p>
                    <p class="mt-1 text-xs {{ $result['baselineGrowthPercent'] === null ? 'text-slate-400' : ($result['baselineGrowthPercent'] >= 0 ? 'text-teal-600' : 'text-rose-600') }}">
                        {{ $result['baselineGrowthPercent'] === null ? '—' : (($result['baselineGrowthPercent'] >= 0 ? '↑ +' : '↓ ').number_format($result['baselineGrowthPercent'], 1).'% vs. baseline') }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Peak Season</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ $result['peakMonth'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">Seasonal index {{ number_format($result['peakSeasonalIndex'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-medium text-slate-400">Trend Analysis</p>
                    <p class="mt-1 flex items-center gap-1.5 text-lg font-semibold {{ $result['trendLabel'] === 'Positive' ? 'text-teal-600' : ($result['trendLabel'] === 'Negative' ? 'text-rose-600' : 'text-slate-600') }}">
                        @if ($result['trendLabel'] === 'Positive')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M15 7h6v6" /></svg>
                        @elseif ($result['trendLabel'] === 'Negative')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l6 6 4-4 8 8M15 17h6v-6" /></svg>
                        @endif
                        {{ $result['trendLabel'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">Overall business trajectory</p>
                </div>
            </div>
        @endif

        @if (! empty($result['diagnostics']))
            <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Model diagnostics</p>
                        <p class="mt-0.5 text-xs text-slate-400">Python SARIMA validation for this series</p>
                    </div>
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700">
                        {{ $result['diagnostics']['selected_order'] ?? 'SARIMA' }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">ADF p-value</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format((float) ($result['diagnostics']['adf_pvalue'] ?? 0), 4) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Stationarity</p>
                        <p class="mt-1 text-base font-semibold {{ ($result['diagnostics']['stationary'] ?? false) ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ ($result['diagnostics']['stationary'] ?? false) ? 'Passed' : 'Needs review' }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">AIC</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format((float) ($result['diagnostics']['aic'] ?? 0), 3) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">BIC</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format((float) ($result['diagnostics']['bic'] ?? 0), 3) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Ljung–Box</p>
                        <p class="mt-1 text-base font-semibold {{ (($result['diagnostics']['residual_ok'] ?? false) ? 'text-emerald-600' : 'text-rose-600') }}">
                            {{ number_format((float) ($result['diagnostics']['ljung_box_pvalue'] ?? 0), 4) }}
                        </p>
                    </div>
                </div>

                <details class="mt-4 border-t border-slate-100 pt-3">
                    <summary class="cursor-pointer text-sm font-medium text-brand-700">How were these values calculated?</summary>
                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        The Python service fits monthly values from {{ $result['sampleSize'] }} data points
                        @if ($result['history']->isNotEmpty())
                            through {{ $result['history']->last()['label'] }}
                        @endif
                        . It tests {{ $result['diagnostics']['candidate_orders_checked'] ?? 4 }} seasonal SARIMA candidates and uses the one with the lowest AIC. The same selected model produces the forecast and diagnostics.
                    </p>
                    <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-xs leading-5 sm:grid-cols-2">
                        <div><dt class="font-semibold text-slate-700">ADF p-value and stationarity</dt><dd class="text-slate-500">An Augmented Dickey–Fuller unit-root test is run on the monthly history. Below 0.05 is evidence the series is stationary; otherwise it is marked “Needs review.” SARIMA differencing can still model non-stationary history.</dd></div>
                        <div><dt class="font-semibold text-slate-700">AIC and BIC</dt><dd class="text-slate-500">These compare model fit while penalizing complexity. Lower is preferred when comparing candidates on this same series; values are not a standalone accuracy score.</dd></div>
                        <div><dt class="font-semibold text-slate-700">Ljung–Box</dt><dd class="text-slate-500">Tests whether fitted-model residuals retain autocorrelation. A p-value above 0.05 means the test did not find significant residual autocorrelation; it does not guarantee forecast accuracy.</dd></div>
                        <div><dt class="font-semibold text-slate-700">Forecast interval and freshness</dt><dd class="text-slate-500">The selected {{ $result['confidence'] }}% interval is calculated from the model’s forecast distribution. @if ($result['generatedAt']) Last computed {{ \Illuminate\Support\Carbon::parse($result['generatedAt'])->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}. @else The snapshot time is not available yet; run a fresh forecast. @endif</dd></div>
                    </dl>
                </details>
            </div>
        @endif

        @if ($isRevenue)
            {{-- Shared chart card: Monthly (actual vs. forecasted, SARIMA) and Daily (plain actuals)
                 render through the same x-revenue-forecast-chart component so both views read as
                 one consistent design — only the data, title, and legend change. --}}
            <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">
                            {{ $isDaily ? 'Daily Revenue (last '.$daily['days'].' days)' : 'Monthly Revenue (actual vs. forecasted)' }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ $isDaily ? $daily['periodLabel'].' — actuals, no forecast' : 'Seasonal peak in '.$result['peakMonth'] }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- Switch between the Monthly forecast and the Daily actuals in place --}}
                        <form method="GET" action="{{ route('forecasting.index') }}">
                            <input type="hidden" name="metric" value="revenue">
                            <input type="hidden" name="years" value="{{ $years }}">
                            <input type="hidden" name="confidence" value="{{ $result['confidence'] }}">
                            <label for="view" class="sr-only">View revenue by</label>
                            <select id="view" name="view" onchange="this.form.submit()"
                                class="rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                <option value="monthly" @selected(! $isDaily)>Monthly revenue</option>
                                <option value="daily" @selected($isDaily)>Daily revenue</option>
                            </select>
                        </form>
                        <button type="button" onclick="openViewDetailsModal()"
                            class="rounded-md bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100">view details</button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-5 text-xs text-slate-500">
                    @if ($isDaily)
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-300"></span> Daily revenue</span>
                    @else
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-slate-800"></span> Historical actual</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-300"></span> Forecasted</span>
                        <span class="flex items-center gap-1.5"><span class="h-0 w-4 border-t-2 border-dashed border-rose-400"></span> {{ $result['confidence'] }}% confidence interval</span>
                    @endif
                </div>

                <div class="mt-5">
                    @if (! $isDaily)
                        <x-revenue-forecast-chart :data="$result['monthlyChart']" />
                    @elseif ($daily['hasData'])
                        <x-revenue-forecast-chart :data="$daily['series']->map(fn ($p) => ['month' => $p['label'], 'actual' => null, 'forecast' => $p['value'], 'ciUpper' => null])" />
                    @else
                        <x-coming-soon title="No recent daily revenue" description="Paid job orders from the last {{ $daily['days'] }} days will show up here." />
                    @endif
                </div>
            </div>

            @if (! $isDaily)
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Peak Revenue Month</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $result['peakMonth'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Seasonal index {{ number_format($result['peakSeasonalIndex'], 2) }} &middot; avg PHP {{ $compact($result['peakValue']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Trough Revenue Month</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $result['troughMonth'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Seasonal index {{ number_format($result['troughSeasonalIndex'], 2) }} &middot; avg PHP {{ $compact($result['troughValue']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Annual Revenue Average</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">PHP {{ $compact($result['annualAvg'] / 12) }}/mo</p>
                        <p class="mt-0.5 text-xs text-slate-400">Trend component {{ $result['trendComponent'] >= 0 ? '+' : '' }}{{ number_format($result['trendComponent'], 2) }}/mo, seasonality removed</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Margin of Error ({{ $result['confidence'] }}%)</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">±PHP {{ $compact($result['ciWidth'] / 2) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Mean half-width across {{ $result['forecast']->count() }} forecast months</p>
                    </div>
                </div>
            @elseif ($daily['hasData'])
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Peak Day</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $daily['peakLabel'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">PHP {{ $compact($daily['peakValue']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Lowest Day</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $daily['lowLabel'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">PHP {{ $compact($daily['lowValue']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Week-over-Week</p>
                        <p class="mt-1 text-lg font-semibold {{ $pctTone($daily['weekOverWeekPercent']) }}">{{ $pctLabel($daily['weekOverWeekPercent']) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Last 7 days vs. prior 7 days</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-medium text-slate-400">Day-over-Day</p>
                        <p class="mt-1 text-lg font-semibold {{ $pctTone($daily['dayOverDayPercent']) }}">{{ $pctLabel($daily['dayOverDayPercent']) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $daily['latestLabel'] }} vs. previous day</p>
                    </div>
                </div>
            @endif
        @else
            <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <form method="GET" action="{{ route('forecasting.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <input type="hidden" name="metric" value="{{ $metric }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div>
                            <label class="text-xs font-medium text-slate-400">{{ $viewLabel }} ({{ $result['sampleSize'] }}mo history)</label>
                            <div class="mt-1 flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600">
                                {{ $viewLabel }}
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                            </div>
                        </div>
                        <div>
                            <label for="confidence" class="text-xs font-medium text-slate-400">Confidence Interval</label>
                            <select id="confidence" name="confidence"
                                class="mt-1 block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                @foreach ([90, 95, 99] as $level)
                                    <option value="{{ $level }}" @selected($result['confidence'] === $level)>{{ $level }}%</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="years" class="text-xs font-medium text-slate-400">Forecast Horizon</label>
                            <select id="years" name="years"
                                class="mt-1 block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                <option value="all" @selected($years === 'all')>All Years</option>
                                <option value="3" @selected($years === '3')>Next 3 Years</option>
                                <option value="1" @selected($years === '1')>Next 1 Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-start">
                        <p class="text-xs text-slate-400">Model: {{ $result['modelOrder'] }}</p>
                        <button type="submit" name="refresh_forecast" value="1"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7Z" /></svg>
                            Run Forecast
                        </button>
                    </div>
                </form>

                {{-- Tab row sits above the chart; the legend sits below it --}}
                <div class="mt-6 flex flex-wrap items-center gap-1 border-b border-slate-100 pb-3">
                    <button type="button" data-fc-tab="annual" onclick="switchForecastTab('annual')"
                        class="fc-tab rounded-md bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">Annual Baseline</button>
                    <button type="button" data-fc-tab="monthly" onclick="switchForecastTab('monthly')"
                        class="fc-tab rounded-md px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">Monthly breakdown</button>
                    <button type="button" data-fc-tab="dow" onclick="switchForecastTab('dow')"
                        class="fc-tab rounded-md px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">Day-of-Week Patterns</button>
                    <button type="button" data-fc-tab="holiday" onclick="switchForecastTab('holiday')"
                        class="fc-tab rounded-md px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">Holiday Impacts</button>
                    <button type="button" onclick="openViewDetailsModal()"
                        class="rounded-md bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100">view details</button>
                </div>

                {{-- Annual Baseline: the main forecast chart + the annual summary stats.
                     Each tab shows only its own panel; switchForecastTab() toggles them. --}}
                <div data-fc-panel="annual">
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-slate-700">{{ $viewLabel }} — actual vs. SARIMA forecast</p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            History through {{ optional($result['history']->last())['label'] ?? '—' }} &middot;
                            {{ $result['forecast']->count() }}-month forecast &middot; model {{ $result['modelOrder'] }}
                        </p>
                    </div>

                    <div class="mt-4">
                        <x-forecast-line-chart :history="$result['history']" :forecast="$result['forecastBand']"
                            :value-prefix="$metric === 'revenue' ? 'PHP ' : ''"
                            :unit-label="$metric === 'revenue' ? '' : $unitWord" />
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="h-0 w-4 border-t-2 border-blue-600"></span> Actual {{ $unitWord }}</span>
                        <span class="flex items-center gap-1.5"><span class="h-0 w-4 border-t-2 border-dashed border-rose-400"></span> SARIMA forecast</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-3 rounded-sm bg-rose-200"></span> {{ $result['confidence'] }}% prediction interval</span>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-5 sm:grid-cols-4">
                        <div>
                            <p class="text-xs font-medium text-slate-400">Peak Month</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($result['peakValue'], 0) }}</p>
                            <p class="text-sm font-medium text-slate-600">{{ $result['peakMonth'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Seasonal index {{ number_format($result['peakSeasonalIndex'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Trough Month</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($result['troughValue'], 0) }}</p>
                            <p class="text-sm font-medium text-slate-600">{{ $result['troughMonth'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Seasonal index {{ number_format($result['troughSeasonalIndex'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Annual Avg.</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($result['annualAvg'], 1) }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ match ($metric) {
                                    'revenue' => 'PHP per year',
                                    'product_sales' => 'Units sold per year',
                                    default => 'Orders per year',
                                } }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-400">CI Width ({{ $result['confidence'] }}%)</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800">±{{ number_format($result['ciWidth'] / 2, 1) }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Upper: {{ number_format($result['ciUpperAvg'], 1) }} Lower: {{ number_format($result['ciLowerAvg'], 1) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Monthly breakdown --}}
                <div data-fc-panel="monthly" class="hidden">
                    @if (! $viewDetails['hasData'])
                        <p class="mt-5 text-sm italic text-slate-400">Not enough history yet for a monthly breakdown.</p>
                    @else
                        <p class="mt-5 text-xs text-slate-400">Per calendar-month profile across {{ $viewDetails['sampleSize'] }} months of history &middot; index is that month's mean relative to the overall mean</p>
                        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <th class="px-4 py-2.5">Month</th>
                                        <th class="px-4 py-2.5 text-right">n</th>
                                        <th class="px-4 py-2.5 text-right">Mean</th>
                                        <th class="px-4 py-2.5 text-right">SD</th>
                                        <th class="px-4 py-2.5 text-right">Min</th>
                                        <th class="px-4 py-2.5 text-right">Max</th>
                                        <th class="px-4 py-2.5 text-right">Index</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($viewDetails['monthlyProfile'] as $row)
                                        <tr class="text-slate-600">
                                            <td class="px-4 py-2 font-medium text-slate-700">{{ $row['month'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['n'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($row['mean'], 1) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($row['sd'], 2) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($row['min'], 1) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($row['max'], 1) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($row['index'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Day-of-Week Patterns --}}
                <div data-fc-panel="dow" class="hidden">
                    @if (! $dow['hasData'])
                        <p class="mt-5 text-sm italic text-slate-400">Not enough history yet for a day-of-week breakdown.</p>
                    @else
                        <p class="mt-5 text-xs text-slate-400">
                            Busiest day <span class="font-semibold text-slate-700">{{ $dow['busiestDay'] }}</span> &middot;
                            quietest day <span class="font-semibold text-slate-700">{{ $dow['quietestDay'] }}</span>
                        </p>
                        <div class="mt-4">
                            <x-bar-chart :data="$dow['days']->map(fn ($d) => ['label' => $d['weekday'], 'value' => $d['value'], 'display' => number_format($d['value']).' · '.number_format($d['index'], 2).'x avg'])" color="teal" :fill="true" />
                        </div>
                    @endif
                </div>

                {{-- Holiday Impacts --}}
                <div data-fc-panel="holiday" class="hidden">
                    @if (! $holiday || ! $holiday['hasData'])
                        <p class="mt-5 text-sm italic text-slate-400">Not enough history yet to measure holiday impact.</p>
                    @else
                        <p class="mt-5 text-xs text-slate-400">
                            Average daily {{ $holiday['unit'] }} within &plusmn;{{ $holiday['windowDays'] }} days of each fixed-date Philippine holiday,
                            versus the all-time daily baseline of {{ number_format($holiday['baseline'], 1) }}. Positive means busier than a typical day.
                        </p>
                        @php $hMax = max(1, $holiday['holidays']->max(fn ($h) => abs($h['liftPercent']))); @endphp
                        <div class="mt-4 space-y-3">
                            @foreach ($holiday['holidays'] as $h)
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3 text-xs font-medium text-slate-600">
                                        <span>{{ $h['name'] }}
                                            <span class="text-slate-400">&middot; {{ $h['date'] }} &middot; {{ $h['observations'] }} yr{{ $h['observations'] === 1 ? '' : 's' }}</span>
                                        </span>
                                        <span class="shrink-0 {{ $h['liftPercent'] >= 0 ? 'text-teal-600' : 'text-rose-600' }}">
                                            {{ $h['liftPercent'] >= 0 ? '+' : '' }}{{ number_format($h['liftPercent'], 1) }}%
                                            <span class="text-slate-400">({{ number_format($h['meanDaily'], 1) }}/day)</span>
                                        </span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $h['liftPercent'] >= 0 ? 'bg-teal-500' : 'bg-rose-400' }}"
                                            style="width: {{ max(2, round((abs($h['liftPercent']) / $hMax) * 100)) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        @endif

        @unless ($isRevenue)
        <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-700">Forecast Accuracy (Backtest)</p>
            <p class="mt-0.5 text-xs text-slate-400">
                @if ($result['accuracyAvailable'])
                    Model trained on earlier history, tested against the most recent {{ $result['holdoutMonths'] }} held-out months.
                @else
                    Not enough history yet to run a hold-out accuracy check.
                @endif
            </p>

            @if ($result['accuracyAvailable'])
                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">MAPE</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ $result['mape'] !== null ? number_format($result['mape'], 1).'%' : '—' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Mean Absolute % Error</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">MAE</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['mae'], 2) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Mean Absolute Error ({{ $result['unit'] }})</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">MSE</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ $result['mse'] !== null ? number_format($result['mse'], 2) : '—' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Mean Squared Error</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">RMSE</p>
                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['rmse'], 2) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Root Mean Squared Error</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-700">Descriptive Statistic Analysis</p>
            <p class="mt-0.5 text-xs text-slate-400">Computed from {{ $result['sampleSize'] }} months of {{ match ($metric) {
                'revenue' => 'revenue',
                'product_sales' => 'product sales',
                default => 'job order',
            } }} history</p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Mean (μ)</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['mean'], 1) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Std. Deviation (σ)</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['stdDev'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Max Observed</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['maxObserved']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Median</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['median'], 1) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Variance</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['variance'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Seasonal Strength</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($result['seasonalStrength'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 sm:col-span-3">
                    <p class="text-xs text-slate-400">Trend Component</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $result['trendComponent'] >= 0 ? '+' : '' }}{{ number_format($result['trendComponent'], 2) }}/mo</p>
                </div>
            </div>
        </div>
        @endunless

        {{-- View Details modal --}}
        @php
            $vd = $viewDetails;
            $vdDaily = ($vd['mode'] ?? 'monthly') === 'daily';
            $vdFmt = fn ($v, $dec = 1) => ($vd['unit'] ?? '') === 'PHP'
                ? 'PHP '.number_format((float) $v)
                : number_format((float) $v, $dec);
            $vdSigned = fn ($v, $dec = 2) => ($v >= 0 ? '+' : '').number_format((float) $v, $dec);
            $vdPct = fn ($v) => $v === null ? '—' : (($v >= 0 ? '+' : '').number_format((float) $v, 1).'%');

            // Descriptive tab reads the same shape for both modes: a "profile" table (calendar
            // month for monthly, Mon–Sun weekday for daily) and a period summary (per year / per week).
            $vdProfile = $vd['hasData'] ? ($vdDaily ? $vd['weekdayProfile'] : $vd['monthlyProfile']) : collect();
            $vdSummary = $vd['hasData'] ? ($vdDaily ? $vd['weeklySummary'] : $vd['annualSummary']) : collect();
            $vdProfileHead = $vdDaily ? 'Weekday' : 'Month';
            $vdProfileTitle = $vdDaily ? 'Weekday Profile' : 'Monthly Profile';
            $vdSummaryTitle = $vdDaily ? 'Weekly Summary' : 'Annual Summary';
            $vdTrendUnit = $vdDaily ? '/day' : '/mo';
            $vdStrengthLabel = $vdDaily ? 'Weekday Strength' : 'Seasonal Strength';
            $vdStrengthValue = $vd['hasData'] ? ($vdDaily ? $vd['weekdayStrength'] : $vd['seasonalStrength']) : 0;
        @endphp
        <div id="view-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4"
            onclick="if (event.target === this) closeViewDetailsModal()">
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                <div class="relative bg-brand-950 px-6 py-5">
                    <button type="button" onclick="closeViewDetailsModal()" class="absolute right-4 top-4 text-white/60 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                    <h2 class="text-xl font-bold text-white">View Details</h2>
                    <p class="mt-0.5 text-xs text-brand-100/70">
                        {{ $vd['title'] }}@if ($vd['hasData']) {{ $vd['periodLabel'] }}@endif
                    </p>
                </div>

                @if (! $vd['hasData'])
                    <div class="px-6 py-16 text-center text-sm italic text-slate-400">
                        {{ $vdDaily ? 'No paid job-order revenue in the last '.$vd['days'].' days.' : 'Not enough history yet for this metric.' }}
                    </div>
                @else
                    <div class="border-b border-slate-200 px-6">
                        <nav class="-mb-px flex gap-6">
                            <button type="button" data-vd-tab="descriptive" onclick="switchViewDetailsTab('descriptive')"
                                class="vd-tab border-b-2 border-brand-600 py-3 text-sm font-semibold text-brand-700">Descriptive</button>
                            @unless ($vdDaily)
                                <button type="button" data-vd-tab="stationarity" onclick="switchViewDetailsTab('stationarity')"
                                    class="vd-tab border-b-2 border-transparent py-3 text-sm font-medium text-slate-400 hover:text-slate-600">Stationarity</button>
                                <button type="button" data-vd-tab="model-fit" onclick="switchViewDetailsTab('model-fit')"
                                    class="vd-tab border-b-2 border-transparent py-3 text-sm font-medium text-slate-400 hover:text-slate-600">Model Fit</button>
                            @endunless
                        </nav>
                    </div>

                    <div class="overflow-y-auto bg-slate-50 px-6 py-6">
                        {{-- Descriptive --}}
                        <div data-vd-panel="descriptive">
                            <p class="mb-4 text-xs text-slate-400">
                                @if ($vdDaily)
                                    Trailing {{ $vd['days'] }}-day actuals ({{ $vd['periodLabel'] }}) &middot; {{ $vd['sampleSize'] }} days &middot; no forecast
                                @else
                                    Fixed {{ $vd['horizonYears'] }}-year forecast horizon ({{ $vd['periodLabel'] }}) &middot; {{ $vd['sampleSize'] }} projected months
                                @endif
                            </p>
                            <h3 class="text-sm font-bold text-slate-800">Central Tendency and Dispersion</h3>
                            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Mean (μ)</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['mean']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Std. Deviation (σ)</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['stdDev'], 2) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Max Observed</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['maxObserved'], 0) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4 sm:row-span-2 sm:flex sm:flex-col sm:justify-center">
                                    <p class="text-xs text-slate-400">Trend Component</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdSigned($vd['trendComponent']) }}{{ $vdTrendUnit }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Median</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['median']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Variance</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['variance'], 1) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">{{ $vdStrengthLabel }}</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($vdStrengthValue, 2) }}</p>
                                </div>
                            </div>

                            <h3 class="mt-6 text-sm font-bold text-slate-800">{{ $vdProfileTitle }}</h3>
                            <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <th class="px-4 py-2.5">{{ $vdProfileHead }}</th>
                                            <th class="px-4 py-2.5 text-right">n</th>
                                            <th class="px-4 py-2.5 text-right">Mean</th>
                                            <th class="px-4 py-2.5 text-right">SD</th>
                                            <th class="px-4 py-2.5 text-right">Min</th>
                                            <th class="px-4 py-2.5 text-right">Max</th>
                                            <th class="px-4 py-2.5 text-right">Index</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($vdProfile as $row)
                                            <tr class="text-slate-600">
                                                <td class="px-4 py-2 font-medium text-slate-700">{{ $row['label'] ?? $row['month'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['n'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $vdFmt($row['mean']) }}</td>
                                                <td class="px-4 py-2 text-right">{{ $vdFmt($row['sd'], 2) }}</td>
                                                <td class="px-4 py-2 text-right">{{ $vdFmt($row['min']) }}</td>
                                                <td class="px-4 py-2 text-right">{{ $vdFmt($row['max']) }}</td>
                                                <td class="px-4 py-2 text-right">{{ number_format($row['index'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 rounded-xl bg-sky-50 p-5">
                                <p class="text-sm font-bold text-slate-700">{{ $vdSummaryTitle }}</p>
                                <div class="mt-4 space-y-3">
                                    @foreach ($vdSummary as $row)
                                        <div class="flex items-center gap-4">
                                            <span class="shrink-0 whitespace-nowrap text-sm font-semibold text-slate-500">{{ $row['label'] ?? $row['year'] }}</span>
                                            <x-sparkline :data="$row['series']" tone="teal" :width="320" :height="28" />
                                            <span class="ml-auto shrink-0 text-xs text-slate-500">
                                                total {{ $vdFmt($row['total'], 0) }} · avg {{ $vdFmt($row['mean']) }} · {{ $row['days'] ?? $row['months'] }}{{ $vdDaily ? 'd' : 'mo' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @unless ($vdDaily)
                        {{-- Stationarity --}}
                        <div data-vd-panel="stationarity" class="hidden">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $vd['isStationary'] ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $vd['isStationary'] ? 'Likely stationary' : 'Non-stationary — differencing applied' }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                Comparing the first and second half of the {{ $vd['historyMonths'] }}-month history. A large shift in the
                                mean or variance across the two halves is the usual sign that the series must be differenced before modelling.
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">1st-half Mean</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['firstHalfMean']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">2nd-half Mean</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['secondHalfMean']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Mean Shift</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdPct($vd['meanShiftPercent']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Variance Shift</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdPct($vd['varShiftPercent']) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">1st-half SD</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['firstHalfSd'], 2) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">2nd-half SD</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vdFmt($vd['secondHalfSd'], 2) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Differencing (d)</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['diffOrder'] }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Seasonal Diff (D), s={{ $vd['seasonalPeriod'] }}</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['seasonalDiffOrder'] }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Model Fit --}}
                        <div data-vd-panel="model-fit" class="hidden">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">Selected Model</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['modelOrder'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Auto-selected by AICc grid search</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs text-slate-400">AICc</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['aicc'] !== null ? number_format($vd['aicc'], 2) : '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Lower is better (corrected for sample size)</p>
                                </div>
                            </div>

                            <p class="mt-6 text-sm font-bold text-slate-800">Forecast Accuracy (Backtest)</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                @if ($vd['accuracyAvailable'])
                                    Trained on earlier history, tested against the most recent {{ $vd['holdoutMonths'] }} held-out months.
                                @else
                                    Not enough history yet to run a hold-out accuracy check.
                                @endif
                            </p>

                            @if ($vd['accuracyAvailable'])
                                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-400">MAPE</p>
                                        <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['mape'] !== null ? number_format($vd['mape'], 1).'%' : '—' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Mean Absolute % Error</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-400">MAE</p>
                                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($vd['mae'], 2) }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Mean Absolute Error ({{ $vd['unit'] }})</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-400">MSE</p>
                                        <p class="mt-1 text-base font-semibold text-slate-800">{{ $vd['mse'] !== null ? number_format($vd['mse'], 2) : '—' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Mean Squared Error</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs text-slate-400">RMSE</p>
                                        <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($vd['rmse'], 2) }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Root Mean Squared Error</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endunless
                    </div>
                @endif
            </div>
        </div>

        {{-- Descriptive Analysis modal --}}
        <div id="descriptive-analysis-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeDescriptiveAnalysisModal()">
            <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between bg-brand-950 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Descriptive Analysis</h2>
                    <button type="button" onclick="closeDescriptiveAnalysisModal()" class="text-white/60 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="max-h-[85vh] space-y-4 overflow-y-auto bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 px-6 py-6">
                    @if (! $descriptive['hasData'])
                        <p class="py-12 text-center text-sm italic text-brand-100/60">no forecast record</p>
                    @else
                        @php
                            // Word-style percent formatting ("↑ up 14%" / "↓ down 5%", whole numbers)
                            // used only on the Sales Revenue tab's Descriptive Analysis, to match its
                            // plain-language design.
                            $pctWords = fn ($v) => $v === null ? '—' : (($v >= 0 ? '↑ up ' : '↓ down ').number_format(abs($v), 0).'%');
                            $trendPhrase = match ($descriptive['trendLabel']) {
                                'Positive' => 'growing steadily',
                                'Negative' => 'slowing down',
                                default => 'holding steady',
                            };
                        @endphp

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <div class="flex flex-col justify-between gap-6 rounded-2xl bg-white p-5 shadow-lg lg:col-span-1">
                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $metric === 'revenue' ? 'Time period' : 'Descriptive Analysis' }}</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $descriptive['periodLabel'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        @if ($metric === 'revenue')
                                            <span class="font-semibold text-teal-600">next {{ $descriptive['periodMonths'] }} months</span>, estimated
                                        @else
                                            <span class="font-semibold text-teal-600">{{ $descriptive['periodMonths'] }}-months</span> forecast
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $metric === 'revenue' ? 'Total orders' : $descriptive['primaryLabel'] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($descriptive['primaryCount']) }}</p>
                                    <p class="mt-1 text-xs font-medium {{ $pctTone($descriptive['primaryYoyPercent']) }}">
                                        @if ($descriptive['primaryYoyPercent'] === null)
                                            No prior-year data
                                        @elseif ($metric === 'revenue')
                                            {{ $descriptive['primaryYoyPercent'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($descriptive['primaryYoyPercent']), 0) }}% {{ $descriptive['primaryYoyPercent'] >= 0 ? 'more' : 'less' }} than last year
                                        @else
                                            {{ $pctLabel($descriptive['primaryYoyPercent']) }} vs last year
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $metric === 'revenue' ? 'Total revenue' : $descriptive['estRevenueLabel'] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $descriptive['estRevenueCompact'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        @if ($metric === 'revenue')
                                            from products and services combined
                                        @else
                                            {{ $descriptive['avgTicketCompact'] }} {{ $descriptive['avgTicketLabel'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 lg:col-span-2">
                                <div class="rounded-2xl bg-white p-5 shadow-lg">
                                    <p class="text-sm font-semibold text-slate-800">{{ $metric === 'revenue' ? "What this means" : "What's Driving This" }}</p>
                                    @if ($metric === 'revenue')
                                        <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                            Sales have been <span class="font-semibold {{ $descriptive['trendLabel'] === 'Positive' ? 'text-teal-600' : ($descriptive['trendLabel'] === 'Negative' ? 'text-rose-600' : 'text-slate-600') }}">{{ $trendPhrase }}</span>.
                                            Business is usually busiest around <span class="font-semibold text-slate-800">{{ $descriptive['peakMonth'] }}</span>,
                                            so expect a similar boost heading into this period.
                                        </p>
                                    @else
                                        <p class="mt-3 text-sm leading-relaxed text-slate-600">
                                            <span class="font-semibold {{ $descriptive['trendLabel'] === 'Positive' ? 'text-teal-600' : ($descriptive['trendLabel'] === 'Negative' ? 'text-rose-600' : 'text-slate-600') }}">{{ $descriptive['trendLabel'] }}</span>
                                            trend ({{ $descriptive['trendComponent'] >= 0 ? '+' : '' }}{{ number_format($descriptive['trendComponent'], 2) }}/mo)
                                            with peak demand typically in <span class="font-semibold text-slate-800">{{ $descriptive['peakMonth'] }}</span>
                                            (seasonal index {{ number_format($descriptive['peakSeasonalIndex'], 2) }}) is shaping the {{ $descriptive['periodLabel'] }} outlook.
                                        </p>
                                    @endif
                                </div>

                                <div class="rounded-2xl bg-white p-5 shadow-lg">
                                    <p class="text-sm font-semibold text-slate-800">{{ $metric === 'revenue' ? 'How are we doing?' : 'Growth and Momentum' }}</p>
                                    @if ($metric === 'revenue')
                                        <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Compared to last year</p>
                                                <p class="mt-2 text-xl font-bold {{ $pctTone($descriptive['vsLastYearPercent']) }}">{{ $pctWords($descriptive['vsLastYearPercent']) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Compared to last period</p>
                                                <p class="mt-2 text-xl font-bold {{ $pctTone($descriptive['vsPriorPeriodPercent']) }}">{{ $pctWords($descriptive['vsPriorPeriodPercent']) }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-3 text-center text-xs text-slate-400">
                                            Want to track progress against a goal? Set a sales target in
                                            <a href="{{ route('settings.index') }}" class="font-medium text-brand-600 hover:underline">Settings</a>.
                                        </p>
                                    @else
                                        <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Vs. Last Year</p>
                                                <p class="mt-2 text-xl font-bold {{ $pctTone($descriptive['vsLastYearPercent']) }}">{{ $pctLabel($descriptive['vsLastYearPercent']) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Vs. Prior Period</p>
                                                <p class="mt-2 text-xl font-bold {{ $pctTone($descriptive['vsPriorPeriodPercent']) }}">{{ $pctLabel($descriptive['vsPriorPeriodPercent']) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Vs. Target</p>
                                                <p class="mt-2 text-xl font-bold {{ $targetProgress ? ($targetProgress['percent'] >= 100 ? 'text-teal-600' : 'text-slate-700') : 'text-slate-400' }}">
                                                    {{ $targetProgress ? $targetProgress['percent'].'%' : '—' }}
                                                </p>
                                            </div>
                                        </div>
                                        @unless ($targetProgress)
                                            <p class="mt-3 text-center text-xs text-slate-400">
                                                Set a 6-year sales revenue target in
                                                <a href="{{ route('settings.index') }}" class="font-medium text-brand-600 hover:underline">Settings</a>
                                                to track progress here.
                                            </p>
                                        @endunless
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white p-5 shadow-lg">
                            @php
                                $demandColors = ['sky', 'teal', 'violet', 'amber', 'fuchsia'];
                                $demandColorClasses = [
                                    'sky' => ['dot' => 'bg-sky-500', 'bar' => 'bg-sky-500'],
                                    'teal' => ['dot' => 'bg-teal-500', 'bar' => 'bg-teal-500'],
                                    'violet' => ['dot' => 'bg-violet-500', 'bar' => 'bg-violet-500'],
                                    'amber' => ['dot' => 'bg-amber-600', 'bar' => 'bg-amber-600'],
                                    'fuchsia' => ['dot' => 'bg-fuchsia-600', 'bar' => 'bg-fuchsia-600'],
                                ];
                            @endphp

                            @if ($metric === 'revenue')
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-800">Top earners &mdash; products and services</p>
                                    <p class="text-xs italic text-slate-400">by revenue</p>
                                </div>

                                @if (empty($topEarners))
                                    <p class="mt-4 text-sm text-slate-400">No paid job-order or sales history yet.</p>
                                @else
                                    @php $totalEarned = array_sum(array_column($topEarners, 'volume')) ?: 1; @endphp

                                    <div class="mt-4 flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        @foreach ($topEarners as $i => $item)
                                            <div class="{{ $demandColorClasses[$demandColors[$i % 5]]['bar'] }}" style="width: {{ max(2, round(($item['volume'] / $totalEarned) * 100)) }}%"></div>
                                        @endforeach
                                    </div>

                                    <div class="mt-2 max-h-64 divide-y divide-slate-100 overflow-y-auto pr-2">
                                        @foreach ($topEarners as $i => $item)
                                            @php $color = $demandColorClasses[$demandColors[$i % 5]]; @endphp
                                            <div class="flex items-center gap-3 py-3">
                                                <span class="h-3 w-3 shrink-0 rounded-sm {{ $color['dot'] }}"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-medium text-slate-700">{{ $item['service'] }}{{ $item['type'] === 'product' ? ' (product)' : '' }}</p>
                                                    <p class="text-xs text-slate-400">{{ $item['typeLabel'] }}</p>
                                                </div>
                                                <x-sparkline :data="$item['trend']" :tone="$item['changePercent'] > 0 ? 'teal' : ($item['changePercent'] < 0 ? 'rose' : 'slate')" />
                                                <div class="flex w-20 shrink-0 flex-col items-end">
                                                    <span class="text-sm font-semibold text-slate-800">&#8369;{{ number_format($item['volume']) }}</span>
                                                    <span class="text-xs font-medium {{ $item['changePercent'] > 0 ? 'text-teal-600' : ($item['changePercent'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                                        {{ $item['changePercent'] > 0 ? '↑ up ' : ($item['changePercent'] < 0 ? '↓ down ' : '') }}{{ number_format(abs($item['changePercent']), 0) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-800">What's in demand?</p>
                                    @if ($metric === 'product_sales')
                                        <select id="demand-basis-select" onchange="switchDemandBasis(this.value)"
                                            class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                            <option value="categories">Top categories</option>
                                            <option value="products">Top products</option>
                                        </select>
                                    @else
                                        <p class="text-xs italic text-slate-400">ranked by volume</p>
                                    @endif
                                </div>

                                @php
                                    $demandLists = $metric === 'product_sales'
                                        ? ['categories' => $topCategories, 'products' => $topProducts]
                                        : ['services' => $topServices];
                                @endphp

                                @foreach ($demandLists as $basis => $items)
                                    <div data-demand-basis="{{ $basis }}" @if (! $loop->first) class="hidden" @endif>
                                        @if (empty($items))
                                            <p class="mt-4 text-sm text-slate-400">No {{ $metric === 'product_sales' ? 'sales' : 'service order' }} history yet.</p>
                                        @else
                                            @php $totalVolume = array_sum(array_column($items, 'volume')) ?: 1; @endphp

                                            <div class="mt-4 flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                                @foreach ($items as $i => $item)
                                                    <div class="{{ $demandColorClasses[$demandColors[$i % 5]]['bar'] }}" style="width: {{ max(2, round(($item['volume'] / $totalVolume) * 100)) }}%"></div>
                                                @endforeach
                                            </div>

                                            <div class="mt-2 max-h-64 divide-y divide-slate-100 overflow-y-auto pr-2">
                                                @foreach ($items as $i => $item)
                                                    @php $color = $demandColorClasses[$demandColors[$i % 5]]; @endphp
                                                    <div class="flex items-center gap-3 py-3">
                                                        <span class="h-3 w-3 shrink-0 rounded-sm {{ $color['dot'] }}"></span>
                                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ $item['service'] }}</span>
                                                        <x-sparkline :data="$item['trend']" :tone="$item['changePercent'] > 0 ? 'teal' : ($item['changePercent'] < 0 ? 'rose' : 'slate')" />
                                                        <div class="flex w-16 shrink-0 flex-col items-end">
                                                            <span class="text-sm font-semibold text-slate-800">{{ $item['volume'] }}</span>
                                                            <span class="text-xs font-medium {{ $item['changePercent'] > 0 ? 'text-teal-600' : ($item['changePercent'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                                                {{ $item['changePercent'] > 0 ? '↑ +' : ($item['changePercent'] < 0 ? '↓ ' : '') }}{{ number_format($item['changePercent'], 1) }}%
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function openDescriptiveAnalysisModal() {
                const el = document.getElementById('descriptive-analysis-modal');
                el.classList.remove('hidden');
                el.classList.add('flex');
            }

            function switchDemandBasis(basis) {
                document.querySelectorAll('[data-demand-basis]').forEach((el) => {
                    el.classList.toggle('hidden', el.dataset.demandBasis !== basis);
                });
            }

            function closeDescriptiveAnalysisModal() {
                const el = document.getElementById('descriptive-analysis-modal');
                el.classList.add('hidden');
                el.classList.remove('flex');
            }

            function openViewDetailsModal() {
                const el = document.getElementById('view-details-modal');
                el.classList.remove('hidden');
                el.classList.add('flex');
            }

            function closeViewDetailsModal() {
                const el = document.getElementById('view-details-modal');
                el.classList.add('hidden');
                el.classList.remove('flex');
            }

            function switchViewDetailsTab(name) {
                const modal = document.getElementById('view-details-modal');
                modal.querySelectorAll('[data-vd-panel]').forEach((p) => {
                    p.classList.toggle('hidden', p.dataset.vdPanel !== name);
                });
                modal.querySelectorAll('.vd-tab').forEach((t) => {
                    const active = t.dataset.vdTab === name;
                    t.classList.toggle('border-brand-600', active);
                    t.classList.toggle('text-brand-700', active);
                    t.classList.toggle('font-semibold', active);
                    t.classList.toggle('border-transparent', !active);
                    t.classList.toggle('text-slate-400', !active);
                    t.classList.toggle('font-medium', !active);
                });
            }

            function switchForecastTab(name) {
                document.querySelectorAll('[data-fc-panel]').forEach((p) => {
                    p.classList.toggle('hidden', p.dataset.fcPanel !== name);
                });
                document.querySelectorAll('.fc-tab').forEach((t) => {
                    const active = t.dataset.fcTab === name;
                    t.classList.toggle('bg-brand-50', active);
                    t.classList.toggle('text-brand-700', active);
                    t.classList.toggle('font-semibold', active);
                    t.classList.toggle('text-slate-500', !active);
                    t.classList.toggle('font-medium', !active);
                    t.classList.toggle('hover:bg-slate-100', !active);
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeViewDetailsModal();
                    closeDescriptiveAnalysisModal();
                }
            });
        </script>
    @endif
    @if ($result['forecastPending'] ?? false)
        <script>
            (() => {
                const key = `forecast-refresh:${window.location.pathname}${window.location.search}`;
                const attempts = Number(sessionStorage.getItem(key) || 0);
                if (attempts < 12) {
                    sessionStorage.setItem(key, String(attempts + 1));
                    window.setTimeout(() => window.location.reload(), 15000);
                }
            })();
        </script>
    @else
        <script>
            (() => {
                const key = `forecast-refresh:${window.location.pathname}${window.location.search}`;
                sessionStorage.removeItem(key);
            })();
        </script>
    @endif
</x-app-layout>
