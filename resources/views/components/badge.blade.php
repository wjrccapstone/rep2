@props(['tone' => 'slate', 'solid' => false])

@php
    $tones = [
        'amber' => 'bg-amber-100 text-amber-700',
        'brand' => 'bg-brand-100 text-brand-700',
        'teal' => 'bg-teal-100 text-teal-700',
        'rose' => 'bg-rose-100 text-rose-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'violet' => 'bg-violet-100 text-violet-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];

    // Opt-in only (Job Orders + Product Catalog pass solid) — every other caller of
    // this component keeps the pastel tones above untouched.
    $solidTones = [
        'amber' => 'bg-yellow-500 text-white',
        'brand' => 'bg-blue-500 text-white',
        'teal' => 'bg-green-500 text-white',
        'rose' => 'bg-red-500 text-white',
        'indigo' => 'bg-indigo-500 text-white',
        'violet' => 'bg-violet-500 text-white',
        'slate' => 'bg-slate-500 text-white',
    ];

    $classes = $solid ? ($solidTones[$tone] ?? $solidTones['slate']) : ($tones[$tone] ?? $tones['slate']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap '.$classes]) }}>
    {{ $slot }}
</span>
