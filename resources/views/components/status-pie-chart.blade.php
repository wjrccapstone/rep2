@props(['segments', 'total'])

@php
    // Same tone system as the rest of the app (JobOrder::statusTone(), Badge component),
    // so a color means the same thing here as everywhere else it appears.
    $toneHex = [
        'amber' => '#f59e0b',
        'brand' => '#2b7fb3',
        'indigo' => '#6366f1',
        'teal' => '#14b8a6',
        'rose' => '#f43f5e',
        'slate' => '#94a3b8',
    ];
    // Inline percentage labels sit inside a saturated fill, so ink is picked per tone
    // for contrast rather than defaulting to white (amber/teal read better with dark ink).
    $toneInk = [
        'amber' => '#1e293b',
        'brand' => '#ffffff',
        'indigo' => '#ffffff',
        'teal' => '#1e293b',
        'rose' => '#ffffff',
        'slate' => '#1e293b',
    ];

    $safeTotal = max(1, $total);
    $cx = 100;
    $cy = 100;
    $outerR = 90;
    $innerR = 54; // ~60% of outerR — a donut hole roomy enough for the center total

    $point = fn (float $deg, float $radius) => [
        $cx + $radius * cos(deg2rad($deg)),
        $cy + $radius * sin(deg2rad($deg)),
    ];

    $cursor = -90.0; // start at 12 o'clock, sweep clockwise
    $slices = collect($segments)->map(function ($s) use (&$cursor, $safeTotal, $point, $cx, $cy, $outerR, $innerR, $toneHex, $toneInk) {
        $pct = ($s['value'] / $safeTotal) * 100;
        $sweep = min(359.99, ($s['value'] / $safeTotal) * 360);
        $start = $cursor;
        $end = $cursor + $sweep;
        $cursor = $end;

        if ($s['value'] <= 0) {
            return [...$s, 'pct' => 0, 'path' => null];
        }

        [$ox1, $oy1] = $point($start, $outerR);
        [$ox2, $oy2] = $point($end, $outerR);
        [$ix2, $iy2] = $point($end, $innerR);
        [$ix1, $iy1] = $point($start, $innerR);
        $largeArc = $sweep > 180 ? 1 : 0;
        $path = 'M'.round($ox1, 2).','.round($oy1, 2)
            ." A{$outerR},{$outerR} 0 {$largeArc} 1 ".round($ox2, 2).','.round($oy2, 2)
            .' L'.round($ix2, 2).','.round($iy2, 2)
            ." A{$innerR},{$innerR} 0 {$largeArc} 0 ".round($ix1, 2).','.round($iy1, 2)
            .' Z';

        // Midpoint for the direct label — only placed for slices wide enough to hold it.
        $mid = $start + $sweep / 2;
        $labelR = ($outerR + $innerR) / 2;
        [$lx, $ly] = $point($mid, $labelR);

        return [
            ...$s,
            'pct' => $pct,
            'path' => $path,
            'hex' => $toneHex[$s['tone']] ?? $toneHex['slate'],
            'ink' => $toneInk[$s['tone']] ?? $toneInk['slate'],
            'labelX' => round($lx, 2),
            'labelY' => round($ly, 2),
            'showLabel' => $sweep >= 24, // ~6.7% — enough angular room for a label
        ];
    });
@endphp

<div class="status-pie-chart flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-4">
    <div class="status-pie-canvas relative shrink-0">
        <svg viewBox="0 0 200 200" class="h-80 w-80">
            @foreach ($slices as $slice)
                @if ($slice['path'])
                    <path d="{{ $slice['path'] }}" fill="{{ $slice['hex'] }}" stroke="#ffffff" stroke-width="2"
                        stroke-linejoin="round" tabindex="0" class="cursor-pointer transition-opacity duration-100 hover:opacity-85 focus:opacity-85 focus:outline-none"
                        data-label="{{ $slice['label'] }}" data-value="{{ $slice['value'] }}" data-pct="{{ number_format($slice['pct'], 1) }}"
                        data-x="{{ $slice['labelX'] }}" data-y="{{ $slice['labelY'] }}"
                        onmouseenter="statusPieShowTip(this)" onmousemove="statusPieMoveTip(event, this)" onmouseleave="statusPieHideTip(this)"
                        onfocus="statusPieShowTip(this)" onblur="statusPieHideTip(this)"></path>
                    @if ($slice['showLabel'])
                        <text x="{{ $slice['labelX'] }}" y="{{ $slice['labelY'] }}" text-anchor="middle" dominant-baseline="middle"
                            class="pointer-events-none select-none" font-size="14" font-weight="600" fill="{{ $slice['ink'] }}">{{ number_format($slice['pct'], 0) }}%</text>
                    @endif
                @endif
            @endforeach

            {{-- Center total: the donut hole's natural use — one hero-ish figure, not a duplicate chart --}}
            <text x="{{ $cx }}" y="{{ $cy - 8 }}" text-anchor="middle" dominant-baseline="middle"
                class="pointer-events-none select-none" font-size="30" font-weight="700" fill="#1e293b">{{ number_format($total) }}</text>
            <text x="{{ $cx }}" y="{{ $cy + 16 }}" text-anchor="middle" dominant-baseline="middle"
                class="pointer-events-none select-none" font-size="12" font-weight="500" fill="#94a3b8">Job Orders</text>
        </svg>

        {{-- Shared tooltip: one element reused across every slice, positioned near the pointer --}}
        <div class="status-pie-tooltip pointer-events-none absolute z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs font-medium text-white shadow-lg"></div>
    </div>

    {{-- Legend: identity + exact value, since color alone (and 5 series) is never enough.
         Fixed width (matches the donut) so the value sits right after the name instead of
         stretching to fill whatever space happens to be free. --}}
    <div class="w-full max-w-xs shrink-0 space-y-2.5 sm:w-auto">
        @foreach ($slices as $slice)
            <div class="flex items-center gap-2.5 text-sm">
                <span class="h-3 w-3 shrink-0 rounded-sm" style="background-color: {{ $toneHex[$slice['tone']] ?? $toneHex['slate'] }}"></span>
                <span class="font-medium text-slate-600">{{ $slice['label'] }}</span>
                <span class="font-semibold text-slate-800">{{ $slice['value'] }}</span>
                <span class="text-slate-400">({{ number_format($slice['pct'], 0) }}%)</span>
            </div>
        @endforeach
    </div>
</div>

<script>
    function statusPieShowTip(el) {
        const wrap = el.closest('.status-pie-canvas');
        const tip = wrap.querySelector('.status-pie-tooltip');
        tip.textContent = `${el.dataset.label}: ${el.dataset.value} (${el.dataset.pct}%)`;
        tip.classList.remove('hidden');

        // Default position (keyboard focus has no pointer coordinates): the slice's own
        // label point, converted from the 0-200 viewBox to the SVG's actual rendered size.
        const svg = wrap.querySelector('svg');
        const scale = svg.getBoundingClientRect().width / 200;
        tip.style.left = (el.dataset.x * scale) + 'px';
        tip.style.top = (el.dataset.y * scale - 10) + 'px';
    }

    function statusPieMoveTip(evt, el) {
        const wrap = el.closest('.status-pie-chart').querySelector('.status-pie-canvas');
        const tip = wrap.querySelector('.status-pie-tooltip');
        const rect = wrap.getBoundingClientRect();
        tip.style.left = (evt.clientX - rect.left) + 'px';
        tip.style.top = (evt.clientY - rect.top - 10) + 'px';
    }

    function statusPieHideTip(el) {
        el.closest('.status-pie-chart').querySelector('.status-pie-tooltip').classList.add('hidden');
    }
</script>
