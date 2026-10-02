@props(['log'])

@php
    $staff = $log->staff;
    $hasDiff = filled($log->before_value) && filled($log->after_value);
    $refUrl = $log->referenceUrl();
@endphp

<tr class="hover:bg-slate-50/60">
    <td class="py-3 pr-3 align-top whitespace-nowrap">
        <p class="font-medium text-slate-700">{{ $log->created_at->format('M j, Y') }}</p>
        <p class="text-xs text-slate-400">{{ $log->created_at->format('g:i A') }}</p>
    </td>
    <td class="py-3 pr-3 align-top">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                {{ $staff?->initials() ?? '—' }}
            </span>
            <div class="leading-tight">
                <p class="font-medium text-slate-700">{{ $staff?->name ?? 'Deleted user' }}</p>
                <p class="text-xs text-slate-400">{{ $staff?->roleLabel() ?? '—' }}</p>
            </div>
        </div>
    </td>
    <td class="py-3 pr-3 align-top">
        <x-badge tone="slate">{{ $log->moduleLabel() }}</x-badge>
    </td>
    <td class="py-3 pr-3 align-top">
        <x-badge :tone="$log->actionTone()">{{ $log->actionLabel() }}</x-badge>
    </td>
    <td class="py-3 pr-3 align-top">
        @if ($refUrl)
            <a href="{{ $refUrl }}" class="font-mono text-xs font-semibold text-brand-600 hover:underline">{{ $log->reference }}</a>
        @else
            <span class="font-mono text-xs text-slate-500">{{ $log->reference ?? '—' }}</span>
        @endif
    </td>
    <td class="py-3 pr-3 align-top max-w-xs">
        <p class="text-slate-700">{{ $log->title }}</p>
        @if ($hasDiff)
            <p class="mt-0.5 text-xs">
                <span class="text-rose-500 line-through decoration-rose-300">{{ $log->before_value }}</span>
                <span class="mx-1 text-slate-300">→</span>
                <span class="font-semibold text-teal-600">{{ $log->after_value }}</span>
            </p>
        @endif
        @if ($log->detail)
            <p class="mt-0.5 text-xs text-slate-400">{{ $log->detail }}</p>
        @endif
    </td>
    <td class="py-3 pl-3 align-top text-right">
        <button type="button" title="View details"
            onclick="openActivityLogModal({{ Js::from($log->modalPayload()) }})"
            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </button>
    </td>
</tr>
