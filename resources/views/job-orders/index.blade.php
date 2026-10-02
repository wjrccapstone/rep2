@php
    // Archive can only ever hold Completed or Cancelled rows (nothing else auto- or
    // manually archives), so its dropdown drops the statuses that can't occur there.
    $statusOptions = ($tab ?? 'active') === 'archive'
        ? [
            '' => 'All Status',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ]
        : [
            '' => 'All Status',
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'for_pickup' => 'For Pick-up',
        ];
    $formStatusLabels = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'for_pickup' => 'For Pick-up',
    ];
    $paymentLabels = ['partial' => 'Partial', 'paid' => 'Paid'];
    $isEditReopen = $errors->any() && old('_editing_id');
    $isTechnician = auth()->user()->role === 'technician';
@endphp
<x-app-layout title="Job Orders">
    <x-page-header title="Job Orders" :subtitle="$isTechnician ? 'Your assigned repair, upgrade, and service tickets.' : 'Track and manage every repair, upgrade, and service ticket.'">
        @unless ($isTechnician)
            <x-slot:actions>
                @if ($tab === 'active')
                    <button type="button" onclick="openArchiveCompletedModal()"
                        class="relative inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                        Archive Completed
                        @if ($archivableJobOrders->count() > 0)
                            <span class="absolute -top-2 -right-2 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-red-600 px-1 text-[11px] font-bold text-white">{{ $archivableJobOrders->count() }}</span>
                        @endif
                    </button>
                    <button type="button" onclick="openJobOrderCreateModal()"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Job Order
                    </button>
                @endif
            </x-slot:actions>
        @endunless
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @unless ($isTechnician)
        <div class="mb-4 flex items-center gap-6 border-b border-slate-200">
            <a href="{{ route('job-orders.index') }}"
                class="flex items-center gap-1.5 border-b-2 px-1 pb-3 text-sm font-medium {{ $tab === 'active' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Job Orders
            </a>
            <a href="{{ route('job-orders.index', ['tab' => 'archive']) }}"
                class="flex items-center gap-1.5 border-b-2 px-1 pb-3 text-sm font-medium {{ $tab === 'archive' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                Archive
                @if ($archivedCount > 0)
                    <span class="rounded-full px-1.5 py-0.5 text-xs font-semibold {{ $tab === 'archive' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">{{ $archivedCount }}</span>
                @endif
            </a>
        </div>
    @endunless

    @if ($tab === 'archive')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
            <div class="flex items-start gap-2.5">
                <svg class="mt-0.5 h-[18px] w-[18px] shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Archived Job Orders</p>
                    <p class="text-xs text-amber-700">{{ $archivedCount }} job order{{ $archivedCount === 1 ? '' : 's' }} in archive. Records moved here can be restored at any time.</p>
                </div>
            </div>
            <a href="{{ route('job-orders.export', ['tab' => 'archive']) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export CSV
            </a>
        </div>
    @endif

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <form method="GET" action="{{ route('job-orders.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            @if ($tab === 'archive')
                <input type="hidden" name="tab" value="archive">
            @endif
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by ID, customer name, or service"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>
            <select name="status" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-48">
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-100">Filter</button>
            @if ($filters['q'] || $filters['status'])
                <a href="{{ route('job-orders.index') }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Reset</a>
            @endif
        </form>

        @if ($tab === 'active')
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <th class="py-3 pr-3">Job Order ID</th>
                        <th class="py-3 pr-3">Customer</th>
                        <th class="py-3 pr-3">Service</th>
                        <th class="py-3 pr-3">Device</th>
                        <th class="py-3 pr-3">Status</th>
                        <th class="py-3 pr-3">Payment</th>
                        <th class="py-3 pr-3">Planned Start</th>
                        <th class="py-3 pr-3">Due Date</th>
                        <th class="py-3 pr-3 text-right">Cost</th>
                        <th class="py-3 pl-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($jobOrders as $jobOrder)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3 pr-3 font-medium text-slate-700">{{ $jobOrder->code }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $jobOrder->customer->name ?? '—' }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $jobOrder->service->name }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $jobOrder->device?->label() }}</td>
                            <td class="py-3 pr-3"><x-badge :tone="$jobOrder->statusTone()" solid>{{ $jobOrder->statusLabel() }}</x-badge></td>
                            @php
                                $paidTotal = (float) ($jobOrder->paid_total ?? 0);
                                $isSettled = $jobOrder->payment_status === 'paid';
                                // Marked Paid (e.g. set directly on the job order rather than via a cashier
                                // sale) settles the balance even if no sale row backs it — don't let a
                                // missing/short paid_total show a "still owes the sticker price" balance.
                                $balance = $isSettled ? 0.0 : max(0, round((float) $jobOrder->cost - $paidTotal, 2));
                                $displayPaid = $isSettled ? (float) $jobOrder->cost : $paidTotal;
                                $hasPayment = $isSettled || $paidTotal > 0;
                            @endphp
                            <td class="py-3 pr-3"><x-badge :tone="$jobOrder->paymentTone()" solid>{{ $jobOrder->paymentLabel() }}</x-badge></td>
                            <td class="py-3 pr-3 text-slate-500">{{ $jobOrder->planned_start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 pr-3 text-slate-500">{{ $jobOrder->due_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 pr-3 text-right">
                                @if ($hasPayment)
                                    {{-- Any payment recorded (or fully settled): the Cost cell tracks what's still owed, not the sticker price. --}}
                                    <span class="inline-flex items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold text-white {{ $balance > 0 ? 'bg-red-500' : 'bg-green-500' }}">{{ number_format($balance, 2) }}</span>
                                    <span class="mt-0.5 block text-[11px] text-slate-400">
                                        {{ $balance > 0 ? 'balance' : 'fully paid' }} · of ₱{{ number_format($jobOrder->cost, 2) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-500 px-2.5 py-1 text-xs font-semibold text-white">{{ number_format($jobOrder->cost, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3 pl-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" title="View details"
                                        onclick="openJobOrderDetailsModal({{ Js::from([
                                            'code' => $jobOrder->code,
                                            'customer' => $jobOrder->customer->name ?? '—',
                                            'service' => $jobOrder->service->name,
                                            'device' => $jobOrder->device?->label() ?? '—',
                                            'status' => $jobOrder->status,
                                            'status_label' => $jobOrder->statusLabel(),
                                            'status_tone' => $jobOrder->statusTone(),
                                            'due_date' => $jobOrder->due_date?->format('Y-m-d') ?? '—',
                                            'created_date' => $jobOrder->created_at->format('Y-m-d'),
                                            'updated_date' => $jobOrder->updated_at->format('Y-m-d'),
                                            'cost' => number_format($jobOrder->cost, 2),
                                            'paid_total' => number_format($displayPaid, 2),
                                            'balance' => number_format($balance, 2),
                                            'has_payment' => $hasPayment,
                                            'technician' => $jobOrder->technician?->user?->name ?? 'Unassigned',
                                        ]) }})"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                    <a href="{{ route('job-orders.service-report', $jobOrder) }}" title="Service report" target="_blank"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.017-1.837-2.185a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.017 1.837-2.185a48.055 48.055 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                                    </a>
                                    @if ($isTechnician)
                                        <button type="button" title="Update status"
                                            onclick="openJobOrderProgressModal({{ Js::from([
                                                'id' => $jobOrder->id,
                                                'code' => $jobOrder->code,
                                                'status' => $jobOrder->status,
                                                'cost' => $jobOrder->cost,
                                                'work_summary' => $jobOrder->work_summary,
                                            ]) }})"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </button>
                                    @else
                                        <button type="button" title="Edit"
                                            onclick="openJobOrderEditModal({{ Js::from([
                                                'id' => $jobOrder->id,
                                                'code' => $jobOrder->code,
                                                'customer_name' => $jobOrder->customer->name ?? '',
                                                'business_name' => $jobOrder->customer->business_name ?? '',
                                                'contact_no' => $jobOrder->customer->contact_no ?? '',
                                                'address' => $jobOrder->customer->address ?? '',
                                                'technician_id' => $jobOrder->technician?->user_id,
                                                'service' => $jobOrder->service->name,
                                                'device_brand' => $jobOrder->device?->device_brand,
                                                'device_type' => $jobOrder->device?->device_type,
                                                'device_model' => $jobOrder->device?->device_model,
                                                'serial_no' => $jobOrder->serial_no,
                                                'status' => $jobOrder->status,
                                                'payment_status' => $jobOrder->payment_status,
                                                'due_date' => $jobOrder->due_date?->format('Y-m-d'),
                                                'issue' => $jobOrder->issue,
                                                'work_summary' => $jobOrder->work_summary,
                                            ]) }})"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                        </button>
                                        <button type="button" title="Delete"
                                            onclick="openJobOrderDeleteModal({{ $jobOrder->id }}, {{ Js::from($jobOrder->code) }})"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-sm text-slate-400">No job orders match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @else
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <th class="py-3 pr-3">Job Order ID</th>
                        <th class="py-3 pr-3">Customer</th>
                        <th class="py-3 pr-3">Service</th>
                        <th class="py-3 pr-3">Status</th>
                        <th class="py-3 pr-3">Payment</th>
                        <th class="py-3 pr-3 text-right">Cost</th>
                        <th class="py-3 pr-3">Archived On</th>
                        <th class="py-3 pl-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($jobOrders as $jobOrder)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3 pr-3 font-medium text-slate-700">{{ $jobOrder->code }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $jobOrder->customer->name ?? '—' }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $jobOrder->service->name }}</td>
                            <td class="py-3 pr-3"><x-badge :tone="$jobOrder->statusTone()" solid>{{ $jobOrder->statusLabel() }}</x-badge></td>
                            <td class="py-3 pr-3"><x-badge :tone="$jobOrder->paymentTone()" solid>{{ $jobOrder->paymentLabel() }}</x-badge></td>
                            <td class="py-3 pr-3 text-right font-medium text-slate-700">{{ number_format($jobOrder->cost, 2) }}</td>
                            <td class="py-3 pr-3 text-slate-500">{{ $jobOrder->archived_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 pl-3 text-right">
                                <form method="POST" action="{{ route('job-orders.restore', $jobOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Restore to active list" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-teal-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-sm text-slate-400">No archived job orders.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        @if ($jobOrders->hasPages())
            <div class="mt-4 border-t border-slate-100 pt-4">
                {{ $jobOrders->links() }}
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-sm">
            <p class="text-xs font-medium text-brand-100">Total Job Orders</p>
            <p class="mt-1 text-2xl font-semibold">{{ $summary['total'] }}</p>
        </div>
        <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-sm">
            <p class="text-xs font-medium text-brand-100">Pending</p>
            <p class="mt-1 text-2xl font-semibold">{{ $summary['pending'] }}</p>
        </div>
        <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-sm">
            <p class="text-xs font-medium text-brand-100">In Progress</p>
            <p class="mt-1 text-2xl font-semibold">{{ $summary['in_progress'] }}</p>
        </div>
        <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-sm">
            <p class="text-xs font-medium text-brand-100">Completed</p>
            <p class="mt-1 text-2xl font-semibold">{{ $summary['completed'] }}</p>
        </div>
    </div>

    @unless ($isTechnician)
    {{-- Create / Edit Job Order modal --}}
    <div id="job-order-form-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('job-order-form-modal')">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 id="jo-form-title" class="text-base font-semibold text-white">Create Job Order</h2>
                <button type="button" onclick="closeModal('job-order-form-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <p class="px-6 text-xs text-brand-200">*Required</p>

            <form id="job-order-form" method="POST" action="{{ route('job-orders.store') }}" class="max-h-[75vh] space-y-4 overflow-y-auto px-6 py-5">
                @csrf
                <input type="hidden" name="_method" id="jo-form-method" value="{{ $isEditReopen ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_editing_id" value="{{ old('_editing_id') }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-brand-100">Job Order ID *</label>
                        <input type="text" id="jo-code-display" readonly value="{{ $nextCode }}"
                            class="mt-1.5 block w-full cursor-not-allowed rounded-lg border border-brand-600 bg-brand-900/60 px-3.5 py-2.5 text-sm text-brand-100">
                        <p class="mt-1 text-xs text-brand-300">Auto-generated</p>
                    </div>
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-brand-100">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" list="customer-options" value="{{ old('customer_name') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        <datalist id="customer-options">
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->name }}">
                            @endforeach
                        </datalist>
                        @error('customer_name') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="business_name" class="block text-sm font-medium text-brand-100">Business Name <span class="font-normal text-brand-300">(if applicable)</span></label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('business_name') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contact_no" class="block text-sm font-medium text-brand-100">Contact No. *</label>
                        <input type="text" id="contact_no" name="contact_no" value="{{ old('contact_no') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('contact_no') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-medium text-brand-100">Address *</label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('address') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="service" class="block text-sm font-medium text-brand-100">Service Type *</label>
                        <input type="text" id="service" name="service" value="{{ old('service') }}" placeholder="e.g. Computer Repair, Laptop Cleaning" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('service') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="device_model" class="block text-sm font-medium text-brand-100">Device Model *</label>
                        <input type="text" id="device_model" name="device_model" value="{{ old('device_model') }}" placeholder="e.g. Inspiron 15, MacBook Pro 2021" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('device_model') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="device_brand" class="block text-sm font-medium text-brand-100">Device Brand</label>
                        <input type="text" id="device_brand" name="device_brand" value="{{ old('device_brand') }}" placeholder="e.g. Dell, HP, Apple"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('device_brand') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="device_type" class="block text-sm font-medium text-brand-100">Device Type</label>
                        <input type="text" id="device_type" name="device_type" value="{{ old('device_type') }}" placeholder="e.g. Laptop, Desktop, Printer"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('device_type') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="serial_no" class="block text-sm font-medium text-brand-100">Serial Number *</label>
                        <input type="text" id="serial_no" name="serial_no" value="{{ old('serial_no') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('serial_no') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    {{-- New job orders always start pending (enforced server-side too) — this field
                         only makes sense once there's an existing status to change, so it's hidden
                         for Create and revealed by openJobOrderEditModal() for Edit. --}}
                    <div id="jo-status-field" class="hidden">
                        <label for="status" class="block text-sm font-medium text-brand-100">Status</label>
                        <select id="status" name="status" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                            @foreach ($formStatusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="jo-due-date-field">
                        <label for="due_date" class="block text-sm font-medium text-brand-100">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('due_date') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="technician_id" class="block text-sm font-medium text-brand-100">Assign Technician</label>
                        <select id="technician_id" name="technician_id"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                            <option value="">Unassigned</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected((string) old('technician_id') === (string) $technician->id)>{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-brand-100">Payment Status</label>
                        {{-- Create: a new job order can't be anything but Partial (no work has been
                             billed yet), so it's shown fixed rather than a choice. Edit: a real select,
                             restored by setPaymentStatusEditable() — toggled alongside the "Paid" option
                             below (also enforced server-side on store()). --}}
                        <div id="payment-status-fixed" class="mt-1.5 block w-full cursor-not-allowed rounded-lg border border-brand-600 bg-brand-900/60 px-3.5 py-2.5 text-sm text-brand-100">Partial</div>
                        <select id="payment_status" name="payment_status" required
                            class="mt-1.5 hidden w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                            @foreach ($paymentLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', 'partial') === $value) @if ($value === 'paid') id="ps-paid-option" @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="issue" class="block text-sm font-medium text-brand-100">Description/Notes</label>
                        <textarea id="issue" name="issue" rows="3" placeholder="Add any additional notes about the job order"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">{{ old('issue') }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="work_summary" class="block text-sm font-medium text-brand-100">Work Summary &amp; Recommendation <span class="font-normal text-brand-300">(optional, shown on the printed service report)</span></label>
                        <textarea id="work_summary" name="work_summary" rows="2"
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">{{ old('work_summary') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('job-order-form-modal')" class="rounded-lg bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="submit" id="jo-form-submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-400">Create a Job Order</button>
                </div>
            </form>
        </div>
    </div>
    @endunless

    {{-- Job Order Details modal --}}
    <div id="job-order-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('job-order-details-modal')">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between px-6 py-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Job Order Details</h2>
                    <p id="jod-code" class="text-xs font-medium text-brand-100"></p>
                </div>
                <button type="button" onclick="closeModal('job-order-details-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="px-6">
                <span id="jod-status" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"></span>
            </div>

            <div class="grid grid-cols-2 gap-x-4 gap-y-3 px-6 py-5 text-sm">
                <div>
                    <p class="text-xs font-medium text-brand-100">Customer Name</p>
                    <p id="jod-customer" class="font-medium text-white"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-brand-100">Due Date</p>
                    <p id="jod-due" class="font-medium text-white"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-brand-100">Service Type</p>
                    <p id="jod-service" class="font-medium text-white"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-brand-100">Created Date</p>
                    <p id="jod-created" class="font-medium text-white"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-brand-100">Device</p>
                    <p id="jod-device" class="font-medium text-white"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-brand-100">Cost</p>
                    <p id="jod-cost" class="font-medium text-white"></p>
                    <p id="jod-cost-note" class="hidden text-xs font-medium text-brand-100/80"></p>
                </div>
            </div>

            <div class="border-t border-brand-600 px-6 py-4">
                <p class="text-xs font-medium text-brand-100">Assigned Technician</p>
                <p id="jod-technician" class="text-sm font-medium text-white"></p>
            </div>

            <div class="border-t border-brand-600 px-6 py-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-100">Job Timeline</p>
                <div class="space-y-3 text-sm">
                    <div class="flex gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-400"></span>
                        <div>
                            <p class="font-medium text-white">Job Created</p>
                            <p id="jod-created-2" class="text-xs text-brand-100"></p>
                        </div>
                    </div>
                    <div id="jod-timeline-status" class="flex gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                        <div>
                            <p id="jod-status-label" class="font-medium text-white"></p>
                            <p id="jod-status-caption" class="text-xs italic text-brand-100"></p>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="closeModal('job-order-details-modal')" class="w-full bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Close</button>
        </div>
    </div>

    @unless ($isTechnician)
    {{-- Delete Job Order modal --}}
    <div id="job-order-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('job-order-delete-modal')">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.625 3.75h.008v.008h-.008v-.008Z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Delete Job Order</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to delete job order <strong id="jod-delete-code" class="font-semibold text-slate-700"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <form id="job-order-delete-form" method="POST" class="mt-5 flex items-center justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('job-order-delete-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Delete</button>
            </form>
        </div>
    </div>

    {{-- Archive Completed Job Orders modal --}}
    <div id="job-order-archive-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('job-order-archive-modal')">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                </div>
                <div>
                    <h3 id="jo-archive-title" class="text-sm font-semibold text-slate-900">Archive completed job orders?</h3>
                    <p class="mt-1 text-sm text-slate-500">These are fully paid and marked Completed. They'll be moved to the Archive tab.</p>
                </div>
            </div>
            <div id="jo-archive-list" class="mt-4 max-h-48 divide-y divide-slate-50 overflow-y-auto rounded-lg border border-slate-100 text-sm"></div>
            <form id="job-order-archive-form" method="POST" action="{{ route('job-orders.archive-completed') }}" class="mt-5 flex items-center justify-end gap-2">
                @csrf
                @method('PATCH')
                <button type="button" onclick="closeModal('job-order-archive-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">Archive</button>
            </form>
        </div>
    </div>
    @endunless

    @if ($isTechnician)
    {{-- Update Progress modal (technician: status + work summary only) --}}
    <div id="job-order-progress-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('job-order-progress-modal')">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Update Job Order</h2>
                    <p id="jop-code" class="text-xs text-brand-300"></p>
                </div>
                <button type="button" onclick="closeModal('job-order-progress-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form id="job-order-progress-form" method="POST" class="space-y-4 px-6 py-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="jop-status" class="block text-sm font-medium text-brand-100">Status</label>
                    <select id="jop-status" name="status" required
                        class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @foreach ($formStatusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="jop-cost" class="block text-sm font-medium text-brand-100">Total Cost (PHP)</label>
                    <input type="number" id="jop-cost" name="cost" step="0.01" min="0"
                        class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label for="jop-work-summary" class="block text-sm font-medium text-brand-100">Work Summary &amp; Recommendation <span class="font-normal text-brand-300">(shown on the printed service report)</span></label>
                    <textarea id="jop-work-summary" name="work_summary" rows="4"
                        class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('job-order-progress-modal')" class="rounded-lg bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-400">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        const JO_UPDATE_URL_TEMPLATE = "{{ route('job-orders.update', ['job_order' => '__ID__']) }}";
        const JO_DESTROY_URL_TEMPLATE = "{{ route('job-orders.destroy', ['job_order' => '__ID__']) }}";
        const JO_STORE_URL = "{{ route('job-orders.store') }}";
        const JO_PROGRESS_URL_TEMPLATE = "{{ route('job-orders.progress.update', ['job_order' => '__ID__']) }}";
        const JO_NEXT_CODE = @json($nextCode);
        const JO_ARCHIVABLE = @json($archivableJobOrders ?? []);

        // With nothing to archive, submit straight through — the server flashes
        // "No completed & paid job orders to archive at the moment." (same pattern
        // as ProductController::destroyAllArchived's empty-archive message) instead
        // of popping a confirm dialog with nothing to confirm.
        function openArchiveCompletedModal() {
            if (JO_ARCHIVABLE.length === 0) {
                document.getElementById('job-order-archive-form').submit();

                return;
            }

            const list = document.getElementById('jo-archive-list');
            list.innerHTML = '';
            JO_ARCHIVABLE.forEach((jobOrder) => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-3 px-3 py-2';

                const code = document.createElement('span');
                code.className = 'font-medium text-slate-700';
                code.textContent = jobOrder.code;

                const customer = document.createElement('span');
                customer.className = 'truncate text-slate-400';
                customer.textContent = jobOrder.customer;

                row.append(code, customer);
                list.appendChild(row);
            });

            document.getElementById('jo-archive-title').textContent = `Archive ${JO_ARCHIVABLE.length} completed job order${JO_ARCHIVABLE.length === 1 ? '' : 's'}?`;
            openModal('job-order-archive-modal');
        }
        const JO_STATUS_LABELS = @json($formStatusLabels);
        const JO_STATUS_TONES = { pending: 'amber', in_progress: 'brand', completed: 'teal', cancelled: 'rose', for_pickup: 'indigo' };
        // Solid variants — mirrors the "solid" prop on the x-badge component, kept in
        // sync so the details modal's status pill matches the table row it was opened from.
        const JO_TONE_CLASSES = {
            amber: 'bg-yellow-500 text-white', brand: 'bg-blue-500 text-white', teal: 'bg-green-500 text-white',
            rose: 'bg-red-500 text-white', indigo: 'bg-indigo-500 text-white', slate: 'bg-slate-500 text-white',
        };

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

        function resetJobOrderForm() {
            document.getElementById('job-order-form').reset();
        }

        function setJobOrderStatusFieldVisible(visible) {
            document.getElementById('jo-status-field').classList.toggle('hidden', !visible);
            // Due Date sits next to Status in the grid — take the full row when Status is hidden.
            document.getElementById('jo-due-date-field').classList.toggle('sm:col-span-2', !visible);
        }

        function setPaymentPaidOptionVisible(visible) {
            const paidOption = document.getElementById('ps-paid-option');
            paidOption.hidden = !visible;
            // A create-mode reset could otherwise leave "Paid" selected but hidden.
            if (!visible && paidOption.selected) {
                document.getElementById('payment_status').value = 'partial';
            }
        }

        // Create: shown as a fixed "Partial" readout, not a choice — the select stays
        // in the DOM (just visually hidden) so its value still submits with the form.
        // Edit: the real select takes over.
        function setPaymentStatusEditable(editable) {
            document.getElementById('payment_status').classList.toggle('hidden', !editable);
            document.getElementById('payment-status-fixed').classList.toggle('hidden', editable);
            if (!editable) {
                document.getElementById('payment_status').value = 'partial';
            }
        }

        function openJobOrderCreateModal() {
            resetJobOrderForm();
            document.getElementById('job-order-form').action = JO_STORE_URL;
            document.getElementById('jo-form-method').value = 'POST';
            document.getElementById('jo-form-title').textContent = 'Create Job Order';
            document.getElementById('jo-form-submit').textContent = 'Create a Job Order';
            document.getElementById('jo-code-display').value = JO_NEXT_CODE;
            document.getElementById('service').value = '';
            setJobOrderStatusFieldVisible(false);
            setPaymentPaidOptionVisible(false);
            setPaymentStatusEditable(false);
            openModal('job-order-form-modal');
        }

        function openJobOrderEditModal(data) {
            resetJobOrderForm();
            document.getElementById('job-order-form').action = JO_UPDATE_URL_TEMPLATE.replace('__ID__', data.id);
            document.getElementById('jo-form-method').value = 'PUT';
            document.getElementById('jo-form-title').textContent = 'Edit Job Order';
            document.getElementById('jo-form-submit').textContent = 'Save Changes';
            document.getElementById('jo-code-display').value = data.code;
            document.getElementById('customer_name').value = data.customer_name || '';
            document.getElementById('business_name').value = data.business_name || '';
            document.getElementById('contact_no').value = data.contact_no || '';
            document.getElementById('address').value = data.address || '';
            document.getElementById('service').value = data.service || '';
            document.getElementById('device_brand').value = data.device_brand || '';
            document.getElementById('device_type').value = data.device_type || '';
            document.getElementById('device_model').value = data.device_model || '';
            document.getElementById('serial_no').value = data.serial_no || '';
            setJobOrderStatusFieldVisible(true);
            setPaymentPaidOptionVisible(true);
            setPaymentStatusEditable(true);
            document.getElementById('status').value = data.status || 'pending';
            document.getElementById('due_date').value = data.due_date || '';
            document.getElementById('technician_id').value = data.technician_id || '';
            document.getElementById('payment_status').value = data.payment_status || 'partial';
            document.getElementById('issue').value = data.issue || '';
            document.getElementById('work_summary').value = data.work_summary || '';
            openModal('job-order-form-modal');
        }

        function openJobOrderProgressModal(data) {
            document.getElementById('job-order-progress-form').action = JO_PROGRESS_URL_TEMPLATE.replace('__ID__', data.id);
            document.getElementById('jop-code').textContent = data.code;
            document.getElementById('jop-status').value = data.status || 'pending';
            document.getElementById('jop-cost').value = data.cost || '';
            document.getElementById('jop-work-summary').value = data.work_summary || '';
            openModal('job-order-progress-modal');
        }

        function openJobOrderDetailsModal(data) {
            document.getElementById('jod-code').textContent = data.code;
            document.getElementById('jod-status').textContent = data.status_label;
            document.getElementById('jod-status').className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ' + (JO_TONE_CLASSES[data.status_tone] || JO_TONE_CLASSES.slate);
            document.getElementById('jod-customer').textContent = data.customer;
            document.getElementById('jod-due').textContent = data.due_date;
            document.getElementById('jod-service').textContent = data.service;
            document.getElementById('jod-created').textContent = data.created_date;
            document.getElementById('jod-device').textContent = data.device;
            const costNote = document.getElementById('jod-cost-note');
            if (data.has_payment) {
                document.getElementById('jod-cost').textContent = data.balance;
                costNote.textContent = (parseFloat(data.balance) > 0 ? 'balance' : 'fully paid')
                    + ' · paid ₱' + data.paid_total + ' of ₱' + data.cost;
                costNote.classList.remove('hidden');
            } else {
                document.getElementById('jod-cost').textContent = data.cost;
                costNote.classList.add('hidden');
            }
            document.getElementById('jod-technician').textContent = data.technician;
            document.getElementById('jod-created-2').textContent = data.created_date;

            const captions = {
                pending: 'Waiting to be started.',
                in_progress: 'Currently working on it.',
                completed: 'Job completed.',
                cancelled: 'Job cancelled.',
                for_pickup: 'Ready for pickup.',
            };
            if (data.status === 'pending') {
                document.getElementById('jod-timeline-status').classList.add('hidden');
            } else {
                document.getElementById('jod-timeline-status').classList.remove('hidden');
                document.getElementById('jod-status-label').textContent = data.status_label;
                document.getElementById('jod-status-caption').textContent = (captions[data.status] || '') + ' ' + data.updated_date;
            }
            openModal('job-order-details-modal');
        }

        function openJobOrderDeleteModal(id, code) {
            document.getElementById('job-order-delete-form').action = JO_DESTROY_URL_TEMPLATE.replace('__ID__', id);
            document.getElementById('jod-delete-code').textContent = code;
            openModal('job-order-delete-modal');
        }

        @if ($errors->any() && old('service') !== null)
            document.addEventListener('DOMContentLoaded', function () {
                @if ($isEditReopen)
                    document.getElementById('job-order-form').action = JO_UPDATE_URL_TEMPLATE.replace('__ID__', '{{ old('_editing_id') }}');
                    document.getElementById('jo-form-title').textContent = 'Edit Job Order';
                    document.getElementById('jo-form-submit').textContent = 'Save Changes';
                    setJobOrderStatusFieldVisible(true);
                    setPaymentPaidOptionVisible(true);
                @else
                    setJobOrderStatusFieldVisible(false);
                    setPaymentPaidOptionVisible(false);
                @endif
                openModal('job-order-form-modal');
            });
        @endif
    </script>
</x-app-layout>
