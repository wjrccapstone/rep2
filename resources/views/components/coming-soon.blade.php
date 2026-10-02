@props(['title', 'description'])

<div class="flex min-h-[60vh] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-16 text-center">
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-100 text-brand-600">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </div>
    <h2 class="mt-4 text-lg font-semibold text-slate-800">{{ $title }}</h2>
    <p class="mt-1.5 max-w-sm text-sm text-slate-500">{{ $description }}</p>
</div>
