@props(['name', 'checked' => false, 'label' => null, 'description' => null])

<label class="flex cursor-pointer items-center justify-between gap-4">
    <span>
        @if ($label)
            <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
        @endif
        @if ($description)
            <span class="block text-xs text-slate-400">{{ $description }}</span>
        @endif
    </span>
    <span class="relative inline-flex shrink-0 items-center">
        <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="peer sr-only">
        <span class="block h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600"></span>
        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
    </span>
</label>
