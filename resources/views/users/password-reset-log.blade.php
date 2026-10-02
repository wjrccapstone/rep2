<x-app-layout title="Password Reset Log">
    <x-user-management-tabs active="password-reset-log" :tab-counts="$tabCounts" :active-staff-count="$activeStaffCount" />

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <form method="GET" action="{{ route('users.password-reset-log') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by staff name or user ID…"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input type="date" name="from" value="{{ $filters['from'] }}"
                    class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <input type="date" name="to" value="{{ $filters['to'] }}"
                    class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <select name="staff" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-40">
                    <option value="">All staff</option>
                    @foreach ($staffOptions as $staff)
                        <option value="{{ $staff->id }}" @selected((string) $filters['staff'] === (string) $staff->id)>{{ $staff->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-100">Search</button>
            </div>
        </form>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <th class="py-3 pr-3">Date &amp; time</th>
                        <th class="py-3 pr-3">Reset by</th>
                        <th class="py-3 pr-3">Target user</th>
                        <th class="py-3 pr-3">User ID</th>
                        <th class="py-3 pl-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($logs as $log)
                        @php
                            $staff = $log->staff;
                            $targetName = \Illuminate\Support\Str::of($log->title)->after('Password reset for ')->rtrim('.');
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
                            <td class="py-3 pr-3 align-top text-slate-700">{{ $targetName }}</td>
                            <td class="py-3 pr-3 align-top font-mono text-xs text-slate-500">{{ $log->reference }}</td>
                            <td class="py-3 pl-3 align-top text-right">
                                <button type="button" title="View details"
                                    onclick="openActivityLogModal({{ Js::from($log->modalPayload()) }})"
                                    class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-400">No password resets match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="mt-4 border-t border-slate-100 pt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <x-activity-log-detail-modal />

    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
    </script>
</x-app-layout>
