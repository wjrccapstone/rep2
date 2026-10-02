@props(['segments', 'total', 'collectedPercent'])

@php
    // Same tones as JobOrder::paymentTone() — a severity scale (paid good, partial at
    // risk), not an arbitrary category palette, so the color reads the same as the
    // payment badges shown on the Job Orders page.
    $toneClasses = [
        'teal' => 'bg-teal-500',
        'amber' => 'bg-[#E9DFCB]',
        'rose' => 'bg-rose-500',
        'slate' => 'bg-slate-400',
    ];

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

    $maxAmount = max(1, collect($segments)->max('amount'));
    $rows = collect($segments)->map(fn ($s) => [...$s, 'widthPct' => max(2, round(($s['amount'] / $maxAmount) * 100, 1))]);
@endphp

<div>
    <p class="text-3xl font-bold text-slate-800">
        {{ $collectedPercent === null ? '—' : number_format($collectedPercent, 0).'%' }}
        <span class="text-base font-medium text-slate-400">revenue collected</span>
    </p>

    {{-- One row per status: label, then a bar sized to its own magnitude (not a shared
         whole), with the amount printed at the bar's tip — legible even when a bar is short.
         Sized (thicker bars, roomier gaps) to carry similar visual weight to the donut chart
         it sits beside, since both sections share one equalized-height grid cell. --}}
    <div class="mt-8 space-y-6">
        @foreach ($rows as $row)
            <div class="flex items-center gap-4">
                <span class="w-16 shrink-0 text-base font-medium text-slate-600">{{ $row['label'] }}</span>
                <div class="h-10 flex-1 overflow-hidden rounded-r-md bg-slate-50">
                    <div class="flex h-full items-center {{ $toneClasses[$row['tone']] ?? $toneClasses['slate'] }} rounded-r-md" style="width: {{ $row['widthPct'] }}%"></div>
                </div>
                <span class="w-28 shrink-0 text-right text-base font-semibold text-slate-700">PHP {{ $compact($row['amount']) }}</span>
            </div>
        @endforeach
    </div>
</div>
