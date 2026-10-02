@props(['data', 'valueSuffix' => ' orders', 'color' => 'brand', 'fill' => false])

@php
    $max = max(1, collect($data)->max('value'));
    $colorClasses = [
        'brand' => 'bg-brand-500 group-hover:bg-brand-600',
        'teal' => 'bg-teal-500 group-hover:bg-teal-600',
        // Dashboard-only additions (sage palette) — kept separate from brand/teal above
        // so the Forecasting page, which also uses this component, is unaffected.
        'sage' => 'bg-[#A8BFA3] group-hover:bg-[#8FAE88]',
        'seafoam' => 'bg-[#98D8C8] group-hover:bg-[#78C7B3]',
        // Dashboard-only blue gradient (Job-Order demand view), assigned per-bar via
        // the $point['tone'] override below based on magnitude, not by $color prop.
        'navyblue' => 'bg-[#2E5AA8] group-hover:bg-[#254A8C]',
        'blue' => 'bg-[#1E88E5] group-hover:bg-[#1A75C4]',
        'skyblue' => 'bg-[#4FC3F7] group-hover:bg-[#3BAEE0]',
        'paleblue' => 'bg-[#BBDEFB] group-hover:bg-[#9FCBF0]',
    ];
    $barColor = $colorClasses[$color] ?? $colorClasses['brand'];

    // When $fill is true the bars stretch to fill the container width (with a
    // sensible min width so many data points still scroll horizontally).
    $rowClass = $fill ? 'w-full' : 'min-w-max';
    $itemClass = $fill ? 'flex-1 min-w-[2.5rem]' : 'w-10 flex-shrink-0';
    $barMaxClass = $fill ? 'max-w-24' : 'max-w-10';
@endphp

{{--
    overflow-x-auto (needed so many bars can scroll horizontally) silently forces
    overflow-y to become 'auto' too in every browser — a well-known CSS quirk where
    one non-visible overflow axis drags the other along with it. That clipped the
    tooltip, which pops up *above* the bar row via a negative offset: it was
    rendering outside this box's top edge and getting cut off there, invisibly,
    on every bar. pt-9 reserves that same space as real padding instead, so the
    tooltip sits inside the box and never depends on overflow behavior at all.
--}}
<div class="w-full overflow-x-auto overflow-y-visible pt-9">
    <div class="flex h-52 gap-2 border-b border-slate-100 sm:gap-4 {{ $rowClass }}">
        @foreach ($data as $point)
            @php $heightPct = $max > 0 ? max(2, round(($point['value'] / $max) * 100)) : 2; @endphp
            <div class="group relative flex h-full cursor-pointer flex-col items-center justify-end {{ $itemClass }}" tabindex="0">
                <div class="pointer-events-none absolute -top-9 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition-opacity duration-100 group-hover:opacity-100 group-focus:opacity-100">
                    {{ $point['display'] ?? ($point['value'].$valueSuffix) }}
                </div>
                <div class="w-full {{ $barMaxClass }} rounded-t-md {{ $colorClasses[$point['tone'] ?? $color] ?? $barColor }} transition-all duration-100 group-hover:-translate-y-0.5 group-focus:-translate-y-0.5" style="height: {{ $heightPct }}%"></div>
            </div>
        @endforeach
    </div>
    <div class="mt-2 flex gap-2 sm:gap-4 {{ $rowClass }}">
        @foreach ($data as $point)
            <div class="text-center text-xs font-medium text-slate-500 {{ $itemClass }}">{{ $point['label'] }}</div>
        @endforeach
    </div>
</div>
