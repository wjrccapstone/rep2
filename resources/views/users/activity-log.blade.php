@php
    $modulePills = ['' => 'All'] + $moduleOptions;
@endphp
<x-app-layout title="Activity Log">
    <x-user-management-tabs active="activity-log" :tab-counts="$tabCounts" :active-staff-count="$activeStaffCount" />

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <form method="GET" action="{{ route('users.activity-log') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by staff name, product ID, invoice no., or job order no…"
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

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($modulePills as $value => $label)
                <a href="{{ route('users.activity-log', array_merge(request()->query(), ['module' => $value])) }}"
                    class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                        {{ $filters['module'] === $value ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <th class="py-3 pr-3">Date &amp; time</th>
                        <th class="py-3 pr-3">Staff</th>
                        <th class="py-3 pr-3">Module</th>
                        <th class="py-3 pr-3">Action</th>
                        <th class="py-3 pr-3">Reference</th>
                        <th class="py-3 pr-3">What changed</th>
                        <th class="py-3 pl-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($logs as $log)
                        <x-activity-log-row :log="$log" />
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-sm text-slate-400">No activity matches your filters.</td>
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
