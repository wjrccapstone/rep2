@props(['data', 'tone' => 'teal', 'width' => 64, 'height' => 24])

@php
    $tones = [
        'teal' => 'stroke-teal-500',
        'rose' => 'stroke-rose-500',
        'slate' => 'stroke-slate-300',
    ];
    $strokeClass = $tones[$tone] ?? $tones['teal'];

    $values = array_values($data);
    $count = count($values);
    $min = $count ? min($values) : 0;
    $max = $count ? max($values) : 0;
    $range = ($max - $min) ?: 1;

    $points = $count > 1
        ? collect($values)->map(function ($v, $i) use ($count, $min, $range, $width, $height) {
            $x = round(($i / ($count - 1)) * $width, 1);
            $y = round($height - (($v - $min) / $range) * $height, 1);

            return "{$x},{$y}";
        })->implode(' ')
        : '';
@endphp

@if ($count > 1)
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" fill="none" class="shrink-0">
        <polyline points="{{ $points }}" class="{{ $strokeClass }}" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
@endif
