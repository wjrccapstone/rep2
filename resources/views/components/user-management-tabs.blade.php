@props(['active', 'tabCounts', 'activeStaffCount'])

@php
    $tabs = [
        'users' => ['label' => 'Users', 'route' => 'users.index', 'count' => $tabCounts['users'], 'icon' => 'user'],
        'password-reset-log' => ['label' => 'Password Reset Log', 'route' => 'users.password-reset-log', 'count' => $tabCounts['password_resets'], 'icon' => 'key'],
        'activity-log' => ['label' => 'Activity Log', 'route' => 'users.activity-log', 'count' => $tabCounts['activity'], 'icon' => 'clock'],
    ];
    $icons = [
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        'key' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
    ];
@endphp

<x-page-header title="User Management" subtitle="Manage staff accounts, password resets, and the record of changes made in the system.">
    <x-slot:actions>
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600">
            <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
            {{ $activeStaffCount }} active staff
        </span>
        @if ($active !== 'users')
            @php
                // Both log tabs export through the same endpoint; the password-reset one just
                // adds a type flag, and both forward whatever filters are currently applied.
                $exportParams = array_merge(request()->query(), $active === 'password-reset-log' ? ['type' => 'password-reset'] : []);
            @endphp
            <a href="{{ route('users.activity-log.export', $exportParams) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export CSV
            </a>
        @else
            <button type="button" onclick="openUserCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add User
            </button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200">
    <nav class="-mb-px flex flex-wrap gap-1">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route($tab['route']) }}"
                class="flex items-center gap-1.5 border-b-2 px-3 pb-3 text-sm font-medium transition
                    {{ $active === $key ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">{!! $icons[$tab['icon']] !!}</svg>
                {{ $tab['label'] }}
                <span class="rounded-full px-1.5 py-0.5 text-xs font-semibold {{ $active === $key ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>
    <span class="pb-3 text-xs text-slate-400">Admin access only</span>
</div>
