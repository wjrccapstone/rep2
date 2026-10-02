@props(['label', 'value', 'icon', 'tone' => 'brand'])

@php
    $tones = [
        'brand' => 'bg-blue-500 text-white',
        'teal' => 'bg-violet-500 text-white',
        'amber' => 'bg-amber-500 text-white',
        'navy' => 'bg-emerald-500 text-white',
    ];

    $icons = [
        'peso' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 4.5h6.75a3.375 3.375 0 0 1 0 6.75H8.25M6 4.5v15m0-7.5h8.25M6 12h2.25" />',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.098a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25v-4.098M20.25 14.15v-1.933a2.25 2.25 0 0 0-.865-1.775l-6-4.615a2.25 2.25 0 0 0-2.77 0l-6 4.615a2.25 2.25 0 0 0-.865 1.775v1.933M20.25 14.15l-7.865 3.63a2.25 2.25 0 0 1-1.77 0L3.75 14.15M9 6.75V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5v2.25" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
    ];
@endphp

<div class="flex items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-lg shadow-black/5 ring-1 ring-slate-100">
    <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-1 truncate text-xl font-semibold text-slate-800">{{ $value }}</p>
    </div>
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $tones[$tone] }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            {!! $icons[$icon] !!}
        </svg>
    </div>
</div>
