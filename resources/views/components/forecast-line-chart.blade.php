@props([
    'history',      // collection of ['label' => 'Sep 2020', 'value' => float]
    'forecast',     // collection of ['label' => 'Sep 2026', 'value' => float, 'lower' => float, 'upper' => float]
    'valuePrefix' => '',
    'valueSuffix' => '',
    'unitLabel' => '',   // e.g. "orders" / "units" — shown in the hover tooltip
])

@php
    $hist = collect($history)->values();
    $fc = collect($forecast)->values();
    $histN = $hist->count();
    $fcN = $fc->count();
    $total = max(2, $histN + $fcN);

    $vals = $hist->pluck('value')
        ->concat($fc->pluck('value'))
        ->concat($fc->pluck('upper'))
        ->filter(fn ($v) => $v !== null);
    $max = $vals->isNotEmpty() ? max((float) $vals->max(), 1) : 1;

    // Round the axis ceiling up to a friendly step so the gridline labels aren't jagged.
    $step = 10 ** max(0, strlen((string) (int) floor($max)) - 2);
    $axisMax = max($step, ceil($max / $step) * $step);

    $toY = fn ($v) => round(100 - min(100, max(0, ($v / $axisMax) * 100)), 2);
    $toX = fn ($i) => round(($i / ($total - 1)) * 100, 2);

    $fmt = function ($v) use ($valuePrefix, $valueSuffix) {
        $v = (float) $v;
        if ($v >= 1_000_000) {
            return $valuePrefix.rtrim(rtrim(number_format($v / 1_000_000, 1), '0'), '.').'M'.$valueSuffix;
        }
        if ($v >= 1_000) {
            return $valuePrefix.number_format($v / 1_000, 0).'K'.$valueSuffix;
        }

        return $valuePrefix.number_format($v).$valueSuffix;
    };

    $grid = collect([1, 0.75, 0.5, 0.25, 0])->map(fn ($f) => ['top' => (1 - $f) * 100, 'label' => $fmt($axisMax * $f)]);

    $lastActualIdx = max(0, $histN - 1);
    $actualLine = $hist->map(fn ($p, $i) => $toX($i).','.$toY($p['value']))->implode(' ');

    // The forecast polyline starts at the last actual point so the two lines connect visually.
    $forecastLine = collect($histN ? [$toX($lastActualIdx).','.$toY($hist->last()['value'])] : [])
        ->concat($fc->map(fn ($p, $i) => $toX($histN + $i).','.$toY($p['value'])))
        ->implode(' ');

    // Prediction-interval band: upper edge left-to-right, then lower edge right-to-left.
    $band = $fc->isNotEmpty()
        ? $fc->map(fn ($p, $i) => $toX($histN + $i).','.$toY($p['upper']))
            ->concat($fc->reverse()->values()->map(fn ($p, $i) => $toX($total - 1 - $i).','.$toY($p['lower'])))
            ->implode(' ')
        : '';

    $forecastFrac = $toX($lastActualIdx) / 100;

    // ~8 evenly spaced x-axis ticks across the combined timeline.
    $labels = $hist->pluck('label')->concat($fc->pluck('label'))->values();
    $tickN = min(8, max(2, $labels->count()));
    $ticks = collect(range(0, $tickN - 1))->map(function ($t) use ($labels, $tickN) {
        $idx = (int) round($t * ($labels->count() - 1) / ($tickN - 1));

        return ['frac' => $labels->count() > 1 ? $idx / ($labels->count() - 1) : 0.5, 'label' => (string) ($labels[$idx] ?? '')];
    });

    // Per-point data for the hover crosshair/tooltip (exact values, not the compacted axis format).
    $points = $hist->map(fn ($p, $i) => [
        'f' => $toX($i) / 100,
        'yTop' => $toY($p['value']),
        'label' => $p['label'],
        'kind' => 'actual',
        'v' => (float) $p['value'],
    ])->concat($fc->map(fn ($p, $i) => [
        'f' => $toX($histN + $i) / 100,
        'yTop' => $toY($p['value']),
        'label' => $p['label'],
        'kind' => 'forecast',
        'v' => (float) $p['value'],
        'lo' => (float) $p['lower'],
        'hi' => (float) $p['upper'],
    ]))->values();
@endphp

<div class="forecast-line-chart w-full"
    data-fc-points="{{ json_encode($points, JSON_UNESCAPED_SLASHES) }}"
    data-fc-prefix="{{ $valuePrefix }}" data-fc-suffix="{{ $valueSuffix }}" data-fc-unit="{{ $unitLabel }}">

    {{-- Smart zoom: a slider that zooms into the timeline itself — the plot stays the same
         height, but the x-axis stretches so you can scroll through month-level detail.
         The setting is remembered per browser. --}}
    <div class="mb-2 flex items-center justify-end gap-2 text-[11px] text-slate-400">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 8v6M8 11h6M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
        </svg>
        <span>Smart zoom</span>
        <input type="range" min="0" max="100" step="1" value="0" data-fc-zoom aria-label="Chart zoom"
            class="h-1 w-32 cursor-pointer appearance-none rounded-full bg-slate-200 accent-brand-600">
    </div>

    <div class="relative h-80" data-fc-plot>
        {{-- pinned value axis (never scrolls) --}}
        @foreach ($grid as $g)
            <div class="pointer-events-none absolute left-0 z-10 -translate-y-1/2 bg-white pr-1 text-[11px] text-slate-400" style="top: {{ $g['top'] }}%">{{ $g['label'] }}</div>
            <div class="pointer-events-none absolute right-0 z-10 -translate-y-1/2 bg-white pl-1 text-right text-[11px] text-slate-400" style="top: {{ $g['top'] }}%">{{ $g['label'] }}</div>
        @endforeach

        {{-- scrollable window; the track inside it widens with zoom --}}
        <div class="absolute inset-x-14 inset-y-0 overflow-x-auto overflow-y-hidden" data-fc-scroll>
            <div class="relative h-full min-w-full" data-fc-track style="width: 100%">
                @foreach ($grid as $g)
                    <div class="absolute inset-x-0 border-t border-slate-100" style="top: {{ $g['top'] }}%"></div>
                @endforeach

                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    @if ($fc->isNotEmpty())
                        <rect x="{{ $forecastFrac * 100 }}" y="0" width="{{ 100 - $forecastFrac * 100 }}" height="100" fill="#fff1f2" opacity="0.7" />
                    @endif
                    @if ($band !== '')
                        <polygon points="{{ $band }}" fill="#fb7185" opacity="0.15" />
                    @endif
                    <polyline points="{{ $actualLine }}" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                    @if ($fc->isNotEmpty())
                        <polyline points="{{ $forecastLine }}" fill="none" stroke="#fb7185" stroke-width="1.5" stroke-dasharray="4 3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                    @endif
                </svg>

                @if ($fc->isNotEmpty())
                    <span class="pointer-events-none absolute top-1 -translate-x-1/2 whitespace-nowrap rounded bg-white/70 px-1.5 text-[10px] font-semibold uppercase tracking-wide text-rose-400"
                        style="left: {{ min(94, ($forecastFrac + (1 - $forecastFrac) / 2) * 100) }}%">Forecast</span>
                @endif

                {{-- hover layer (positioned by % of the track, so it tracks with zoom + scroll) --}}
                <div class="pointer-events-none absolute inset-y-0 hidden w-px bg-slate-400/70" data-fc-crosshair></div>
                <div class="pointer-events-none absolute hidden h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow" data-fc-marker></div>
                <div class="pointer-events-none absolute z-20 hidden -translate-x-1/2 whitespace-pre rounded-md bg-slate-800 px-2 py-1 text-[11px] font-medium leading-tight text-white shadow-lg" data-fc-tooltip></div>
                <div class="absolute inset-0 cursor-crosshair focus:outline-none" tabindex="0" data-fc-overlay></div>
            </div>
        </div>
    </div>

    <div class="mt-2 h-4 overflow-hidden px-14" data-fc-xaxis>
        <div class="relative h-full min-w-full" data-fc-xaxis-track style="width: 100%">
            @foreach ($ticks as $tk)
                <span class="absolute -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-slate-400"
                    style="left: {{ $tk['frac'] * 100 }}%">{{ $tk['label'] }}</span>
            @endforeach
        </div>
    </div>
</div>

<script>
(function () {
    const MAX_FACTOR = 8;   // slider at 100% => timeline 8x wider than the window

    function initForecastChart(root) {
        if (root.dataset.fcReady) return;
        root.dataset.fcReady = '1';

        const points = JSON.parse(root.dataset.fcPoints || '[]');
        const prefix = root.dataset.fcPrefix || '';
        const suffix = root.dataset.fcSuffix || '';
        const unit = root.dataset.fcUnit ? ' ' + root.dataset.fcUnit : '';

        const scroll = root.querySelector('[data-fc-scroll]');
        const track = root.querySelector('[data-fc-track]');
        const xTrack = root.querySelector('[data-fc-xaxis-track]');
        const overlay = root.querySelector('[data-fc-overlay]');
        const crosshair = root.querySelector('[data-fc-crosshair]');
        const marker = root.querySelector('[data-fc-marker]');
        const tip = root.querySelector('[data-fc-tooltip]');
        const zoom = root.querySelector('[data-fc-zoom]');

        let activeIdx = -1;

        function syncXAxis() {
            xTrack.style.transform = 'translateX(' + (-scroll.scrollLeft) + 'px)';
        }

        // ---- smart zoom slider: widen the timeline, keep the current view centred ----
        function applyZoom(pct) {
            pct = Math.max(0, Math.min(100, Number(pct)));
            if (Number.isNaN(pct)) pct = 0;

            const before = scroll.scrollWidth || scroll.clientWidth || 1;
            const centreFrac = (scroll.scrollLeft + scroll.clientWidth / 2) / before;

            const widthPct = (1 + (MAX_FACTOR - 1) * pct / 100) * 100;
            track.style.width = widthPct + '%';
            xTrack.style.width = widthPct + '%';

            const after = scroll.scrollWidth;
            scroll.scrollLeft = centreFrac * after - scroll.clientWidth / 2;
            syncXAxis();

            if (zoom.value != pct) zoom.value = pct;
            try { localStorage.setItem('fcChartZoom', pct); } catch (e) {}
        }
        zoom.addEventListener('input', () => applyZoom(zoom.value));
        scroll.addEventListener('scroll', syncXAxis);

        let savedZoom = 0;
        try {
            const s = localStorage.getItem('fcChartZoom');
            if (s !== null && s !== '') savedZoom = Number(s);
        } catch (e) {}
        applyZoom(savedZoom);

        // ---- hover / crosshair (everything positioned by % of the widening track) ----
        const fmt = (v) => prefix + Math.round(v).toLocaleString() + suffix;

        function show(idx) {
            const p = points[idx];
            if (!p) return;
            activeIdx = idx;
            const leftPct = (p.f * 100) + '%';

            crosshair.style.left = leftPct;
            crosshair.classList.remove('hidden');

            marker.style.left = leftPct;
            marker.style.top = p.yTop + '%';
            marker.style.backgroundColor = p.kind === 'forecast' ? '#fb7185' : '#2563eb';
            marker.classList.remove('hidden');

            let text = p.label + '\n';
            if (p.kind === 'forecast') {
                text += 'Forecast: ' + fmt(p.v) + unit + '\n95% interval: ' + fmt(p.lo) + ' – ' + fmt(p.hi);
            } else {
                text += 'Actual: ' + fmt(p.v) + unit;
            }
            tip.textContent = text;
            tip.style.left = leftPct;
            // flip below the point near the top, above it otherwise, so it stays in view
            tip.style.top = (p.yTop < 22 ? p.yTop + 6 : p.yTop - 6) + '%';
            tip.style.transform = p.yTop < 22 ? 'translate(-50%, 0)' : 'translate(-50%, -100%)';
            tip.classList.remove('hidden');
        }

        function hide() {
            activeIdx = -1;
            crosshair.classList.add('hidden');
            marker.classList.add('hidden');
            tip.classList.add('hidden');
        }

        function nearestIdx(clientX) {
            const rect = track.getBoundingClientRect();
            const frac = (clientX - rect.left) / rect.width;
            let best = 0, bestD = Infinity;
            for (let i = 0; i < points.length; i++) {
                const d = Math.abs(points[i].f - frac);
                if (d < bestD) { bestD = d; best = i; }
            }
            return best;
        }

        function reveal(idx) {
            show(idx);
            const p = points[idx];
            if (!p) return;
            const target = p.f * scroll.scrollWidth;
            if (target < scroll.scrollLeft + 20) scroll.scrollLeft = target - 40;
            else if (target > scroll.scrollLeft + scroll.clientWidth - 20) scroll.scrollLeft = target - scroll.clientWidth + 40;
        }

        overlay.addEventListener('pointermove', (e) => show(nearestIdx(e.clientX)));
        overlay.addEventListener('pointerleave', hide);
        overlay.addEventListener('focus', () => reveal(activeIdx >= 0 ? activeIdx : 0));
        overlay.addEventListener('blur', hide);
        overlay.addEventListener('keydown', (e) => {
            const cur = activeIdx < 0 ? 0 : activeIdx;
            if (e.key === 'ArrowRight') { e.preventDefault(); reveal(Math.min(points.length - 1, cur + 1)); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); reveal(Math.max(0, cur - 1)); }
        });
    }

    document.querySelectorAll('.forecast-line-chart').forEach(initForecastChart);
})();
</script>
