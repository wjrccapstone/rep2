@props(['title', 'subtitle' => null, 'dark' => false])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold sm:text-[28px] {{ $dark ? 'text-white' : 'text-slate-800' }}">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 inline-block rounded-lg px-3 py-1.5 text-sm {{ $dark ? 'bg-white/10 text-brand-100' : 'bg-brand-100/70 text-brand-700' }}">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
