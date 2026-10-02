@props(['data', 'valuePrefix' => 'PHP '])

@php
    $points = collect($data)->values();
    $n = max(1, $points->count());

    $allValues = $points->flatMap(fn ($p) => [$p['actual'], $p['forecast'], $p['ciUpper']])->filter(fn ($v) => $v !== null);
    $max = $allValues->isNotEmpty() ? max($allValues->max(), 1) : 1;

    // Round the axis ceiling up to a friendly step so gridline labels aren't jagged numbers.
    $step = 10 ** max(0, strlen((string) floor($max)) - 2);
    $axisMax = ceil($max / $step) * $step ?: 1;

    $toY = fn ($v) => 100 - min(100, ($v / $axisMax) * 100);
    $toX = fn ($i) => ((($i + 0.5) / $n) * 100);

    $formatAxis = function ($v) use ($valuePrefix) {
        if ($v >= 1_000_000) {
            return $valuePrefix.number_format($v / 1_000_000, 1).'M';
        }
        if ($v >= 1_000) {
            return $valuePrefix.number_format($v / 1_000, 0).'K';
        }

        return $valuePrefix.number_format($v);
    };

    $gridLines = collect([1, 0.75, 0.5, 0.25, 0])->map(fn ($f) => ['pct' => (1 - $f) * 100, 'label' => $formatAxis($axisMax * $f)]);

    // Contiguous [x, y] runs for a series (a null value breaks the run).
    $segmentsOf = function (string $key) use ($points, $toX, $toY) {
        $segments = [];
        $current = [];
        foreach ($points as $i => $p) {
            if ($p[$key] === null) {
                if (count($current) > 1) {
                    $segments[] = $current;
                }
                $current = [];

                continue;
            }
            $current[] = [round($toX($i), 2), round($toY($p[$key]), 2)];
        }
        if (count($current) > 1) {
            $segments[] = $current;
        }

        return $segments;
    };

    $polyline = fn ($segs) => collect($segs)->map(fn ($s) => collect($s)->map(fn ($xy) => $xy[0].','.$xy[1])->implode(' '));
    // Same run, closed down to the baseline for a soft area fill.
    $areaPolys = fn ($segs) => collect($segs)->map(fn ($s) => $s[0][0].',100 '.collect($s)->map(fn ($xy) => $xy[0].','.$xy[1])->implode(' ').' '.$s[count($s) - 1][0].',100');

    $actualSegs = $segmentsOf('actual');
    $ciSegs = $segmentsOf('ciUpper');

    // Per-point data for the hover crosshair/tooltip (exact values).
    $jsPoints = $points->map(fn ($p, $i) => [
        'f' => round($toX($i) / 100, 4),
        'month' => $p['month'],
        'actual' => $p['actual'],
        'forecast' => $p['forecast'],
        'ciUpper' => $p['ciUpper'],
        'yActual' => $p['actual'] !== null ? round($toY($p['actual']), 2) : null,
        'yForecast' => $p['forecast'] !== null ? round($toY($p['forecast']), 2) : null,
    ])->values();
@endphp

<div class="revenue-forecast-chart w-full"
    data-rfc-points="{{ json_encode($jsPoints, JSON_UNESCAPED_SLASHES) }}"
    data-rfc-prefix="{{ $valuePrefix }}">
    <div class="relative h-72 pl-14 pr-14 sm:h-80" data-rfc-plot>
        {{-- Gridlines + shared left/right axis labels --}}
        @foreach ($gridLines as $line)
            <div class="absolute inset-x-14 border-t border-slate-100" style="top: {{ $line['pct'] }}%"></div>
            <div class="absolute left-0 -translate-y-1/2 text-[11px] text-slate-400" style="top: {{ $line['pct'] }}%">{{ $line['label'] }}</div>
            <div class="absolute right-0 -translate-y-1/2 text-right text-[11px] text-slate-400" style="top: {{ $line['pct'] }}%">{{ $line['label'] }}</div>
        @endforeach

        {{-- Forecast bars --}}
        <div class="pointer-events-none absolute inset-x-14 inset-y-0 flex items-end gap-1.5 sm:gap-3">
            @foreach ($points as $point)
                <div class="flex h-full flex-1 flex-col items-center justify-end">
                    @if ($point['forecast'] !== null)
                        <div class="w-full max-w-9 rounded-t-[5px] bg-gradient-to-t from-indigo-300 to-indigo-200"
                            style="height: {{ max(2, round(($point['forecast'] / $axisMax) * 100)) }}%"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Actual trend + confidence-interval reference line --}}
        <svg class="pointer-events-none absolute inset-x-14 inset-y-0 h-full w-[calc(100%-7rem)]" viewBox="0 0 100 100" preserveAspectRatio="none">
            @foreach ($areaPolys($actualSegs) as $poly)
                <polygon points="{{ $poly }}" fill="#334155" opacity="0.07" />
            @endforeach
            @foreach ($polyline($ciSegs) as $seg)
                <polyline points="{{ $seg }}" fill="none" stroke="#fda4af" stroke-width="1" stroke-dasharray="2 2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            @endforeach
            @foreach ($polyline($actualSegs) as $seg)
                <polyline points="{{ $seg }}" fill="none" stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
            @endforeach
        </svg>

        {{-- Hover layer --}}
        <div class="pointer-events-none absolute inset-y-0 hidden w-px bg-slate-400/70" data-rfc-crosshair></div>
        <div class="pointer-events-none absolute hidden h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-slate-800 shadow" data-rfc-marker-a></div>
        <div class="pointer-events-none absolute hidden h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-indigo-400 shadow" data-rfc-marker-f></div>
        <div class="pointer-events-none absolute z-20 hidden -translate-x-1/2 whitespace-pre rounded-md bg-slate-800 px-2 py-1 text-[11px] font-medium leading-tight text-white shadow-lg" data-rfc-tooltip></div>
        <div class="absolute inset-x-14 inset-y-0 cursor-crosshair focus:outline-none" tabindex="0" data-rfc-overlay></div>
    </div>

    <div class="mt-2 flex gap-1 pl-14 pr-14 sm:gap-2">
        @foreach ($points as $point)
            <div class="flex-1 text-center text-xs font-medium text-slate-500">{{ $point['month'] }}</div>
        @endforeach
    </div>
</div>

<script>
(function () {
    function initRevenueChart(root) {
        if (root.dataset.rfcReady) return;
        root.dataset.rfcReady = '1';

        const points = JSON.parse(root.dataset.rfcPoints || '[]');
        const prefix = root.dataset.rfcPrefix || '';

        const plot = root.querySelector('[data-rfc-plot]');
        const overlay = root.querySelector('[data-rfc-overlay]');
        const crosshair = root.querySelector('[data-rfc-crosshair]');
        const markerA = root.querySelector('[data-rfc-marker-a]');
        const markerF = root.querySelector('[data-rfc-marker-f]');
        const tip = root.querySelector('[data-rfc-tooltip]');

        const GUT = 56; // pl-14 / pr-14 = 3.5rem
        const fmt = (v) => prefix + Math.round(v).toLocaleString();
        let activeIdx = -1;

        function pxLeft(f) {
            const w = plot.getBoundingClientRect().width;
            return GUT + (w - 2 * GUT) * f;
        }

        function show(idx) {
            const p = points[idx];
            if (!p) return;
            activeIdx = idx;
            const px = pxLeft(p.f);

            crosshair.style.left = px + 'px';
            crosshair.classList.remove('hidden');

            markerA.classList.toggle('hidden', p.yActual === null);
            if (p.yActual !== null) { markerA.style.left = px + 'px'; markerA.style.top = p.yActual + '%'; }
            markerF.classList.toggle('hidden', p.yForecast === null);
            if (p.yForecast !== null) { markerF.style.left = px + 'px'; markerF.style.top = p.yForecast + '%'; }

            const lines = [p.month];
            if (p.actual === null && p.ciUpper === null && p.forecast !== null) {
                // single-series view (e.g. Daily Revenue): the bar's value is the whole story
                lines.push(fmt(p.forecast));
            } else {
                if (p.actual !== null) lines.push('Actual: ' + fmt(p.actual));
                if (p.forecast !== null) lines.push('Forecast: ' + fmt(p.forecast));
                if (p.ciUpper !== null) lines.push('95% CI upper: ' + fmt(p.ciUpper));
            }
            tip.textContent = lines.join('\n');

            const w = plot.getBoundingClientRect().width;
            tip.style.left = Math.min(Math.max(px, 46), w - 46) + 'px';
            const anchorY = p.yForecast !== null ? Math.min(p.yForecast, p.yActual === null ? 100 : p.yActual) : (p.yActual ?? 50);
            tip.style.top = (anchorY < 24 ? anchorY + 4 : anchorY - 4) + '%';
            tip.style.transform = anchorY < 24 ? 'translate(-50%, 0)' : 'translate(-50%, -100%)';
            tip.classList.remove('hidden');
        }

        function hide() {
            activeIdx = -1;
            [crosshair, markerA, markerF, tip].forEach((el) => el.classList.add('hidden'));
        }

        function nearestIdx(clientX) {
            const rect = plot.getBoundingClientRect();
            const frac = (clientX - rect.left - GUT) / (rect.width - 2 * GUT);
            let best = 0, bestD = Infinity;
            for (let i = 0; i < points.length; i++) {
                const d = Math.abs(points[i].f - frac);
                if (d < bestD) { bestD = d; best = i; }
            }
            return best;
        }

        overlay.addEventListener('pointermove', (e) => show(nearestIdx(e.clientX)));
        overlay.addEventListener('pointerleave', hide);
        overlay.addEventListener('focus', () => show(activeIdx >= 0 ? activeIdx : 0));
        overlay.addEventListener('blur', hide);
        overlay.addEventListener('keydown', (e) => {
            const cur = activeIdx < 0 ? 0 : activeIdx;
            if (e.key === 'ArrowRight') { e.preventDefault(); show(Math.min(points.length - 1, cur + 1)); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); show(Math.max(0, cur - 1)); }
        });
    }

    document.querySelectorAll('.revenue-forecast-chart').forEach(initRevenueChart);
})();
</script>
