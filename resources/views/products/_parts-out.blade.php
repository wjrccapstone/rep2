@php
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $role = auth()->user()->role;
    $isAdmin = $role === 'admin';
    $colspan = $isAdmin ? 8 : 7;
@endphp

@if (session('error'))
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
@endif

@if ($role === 'technician')
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
        <svg class="h-5 w-5 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25h.008v5.25h.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
        <p class="text-sm text-brand-800"><strong>Requests are sent to Admin for approval.</strong> Stock is not affected until an Admin accepts your Parts Out request.</p>
    </div>
@endif

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <p class="text-xs font-medium text-slate-400">Parts Issued Today</p>
        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $stats['issuedToday'] }} {{ \Illuminate\Support\Str::plural('item', $stats['issuedToday']) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <p class="text-xs font-medium text-slate-400">Parts Cost &mdash; This Month</p>
        <p class="mt-1 text-2xl font-semibold text-teal-600">{{ $peso($stats['costThisMonth']) }}</p>
    </div>
    <div class="rounded-2xl p-5 shadow-sm {{ $stats['pendingApproval'] > 0 ? 'bg-amber-50 ring-1 ring-amber-300' : 'bg-white ring-1 ring-slate-100' }}">
        <div class="flex items-center gap-1.5">
            @if ($stats['pendingApproval'] > 0)
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
            @endif
            <p class="text-xs font-medium {{ $stats['pendingApproval'] > 0 ? 'text-amber-700' : 'text-slate-400' }}">Pending Approval</p>
        </div>
        <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $stats['pendingApproval'] }} {{ Str::plural('request', $stats['pendingApproval']) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <p class="text-xs font-medium text-slate-400">Products Low on Stock</p>
        <p class="mt-1 text-2xl font-semibold text-rose-600">{{ $stats['lowStockProducts'] }} {{ Str::plural('product', $stats['lowStockProducts']) }}</p>
    </div>
</div>

<div class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="hidden" name="tab" value="parts-out">
        <input type="hidden" name="technician" value="{{ $filters['technician'] }}">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by job order, customer, or part..."
                class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30">
        </div>
        <div class="flex overflow-hidden rounded-lg border border-slate-200">
            @foreach (['' => 'All', 'today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $value => $label)
                <a href="{{ route('products.index', ['tab' => 'parts-out', 'q' => $filters['q'], 'technician' => $filters['technician'], 'range' => $value]) }}"
                    class="px-4 py-2.5 text-sm font-medium {{ $filters['range'] === $value ? 'bg-brand-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }} {{ $loop->first ? '' : 'border-l border-slate-200' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if ($filters['q'] || $filters['range'] || $filters['technician'])
            <a href="{{ route('products.index', ['tab' => 'parts-out']) }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Reset</a>
        @endif
    </form>

    @unless ($role === 'technician')
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('products.index', ['tab' => 'parts-out', 'q' => $filters['q'], 'range' => $filters['range']]) }}"
                class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $filters['technician'] === '' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All Technicians</a>
            @foreach ($technicians as $technician)
                <a href="{{ route('products.index', ['tab' => 'parts-out', 'q' => $filters['q'], 'range' => $filters['range'], 'technician' => $technician->id]) }}"
                    class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ (string) $filters['technician'] === (string) $technician->id ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $technician->user->name }}</a>
            @endforeach
        </div>
    @endunless

    <div class="mt-5 overflow-x-auto rounded-xl ring-1 ring-slate-100">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="w-8 px-4 py-3"></th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Job Order</th>
                    <th class="px-4 py-3">Technician</th>
                    <th class="px-4 py-3">Parts</th>
                    <th class="px-4 py-3">Cost</th>
                    <th class="px-4 py-3">Status</th>
                    @if ($isAdmin)
                        <th class="px-4 py-3 text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($partOuts as $partOut)
                    <tr class="cursor-pointer hover:bg-slate-50/60" onclick="togglePartOutRow({{ $partOut->id }})">
                        <td class="px-4 py-3 text-slate-400">
                            <svg id="po-chevron-{{ $partOut->id }}" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $partOut->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-700">{{ $partOut->jobOrder->code }}</span><br>
                            <span class="text-xs text-slate-400">{{ $partOut->jobOrder->customer->business_name ?: $partOut->jobOrder->customer->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $partOut->jobOrder->technician?->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $partOut->items_count }} {{ Str::plural('part', $partOut->items_count) }}</td>
                        <td class="px-4 py-3 font-medium text-slate-700">{{ $peso($partOut->total_cost) }}</td>
                        <td class="px-4 py-3">
                            <div>
                                <x-badge :tone="$partOut->statusTone()">{{ $partOut->statusLabel() }}</x-badge>
                                @if ($partOut->status === 'rejected')
                                    <p class="mt-1 max-w-[220px] text-xs text-slate-400">{{ $partOut->rejectedBy?->name ?? 'Admin' }}: &ldquo;{{ $partOut->rejection_reason }}&rdquo;</p>
                                @elseif ($partOut->status === 'pending_billing' && $partOut->approved_by)
                                    <p class="mt-1 text-xs text-slate-400">Approved by {{ $partOut->approvedBy?->name ?? 'Admin' }}</p>
                                @endif
                            </div>
                        </td>
                        @if ($isAdmin)
                            <td class="px-4 py-3 text-right">
                                @if ($partOut->status === 'pending_approval')
                                    <div class="inline-flex items-center gap-2" onclick="event.stopPropagation()">
                                        <form method="POST" action="{{ route('parts-out.approve', $partOut) }}" onsubmit="return confirm('Approve {{ $partOut->code }}? This will deduct stock and add {{ $peso($partOut->total_cost) }} to the job order total.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('parts-out.reject', $partOut) }}" onsubmit="return PartsOut.submitReject(this, '{{ $partOut->code }}')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="reason" class="po-reject-reason">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($partOut->status === 'pending_billing')
                                    <form method="POST" action="{{ route('parts-out.bill', $partOut) }}" onclick="event.stopPropagation()" onsubmit="return confirm('Mark {{ $partOut->code }} as billed?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700 hover:underline">Mark Billed</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                    <tr id="po-detail-{{ $partOut->id }}" class="hidden">
                        <td colspan="{{ $colspan }}" class="px-4 py-3">
                            <div class="rounded-xl border {{ $isAdmin && $partOut->status === 'pending_approval' ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50' }} p-4 text-xs">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                                    <div><span class="text-brand-600">Customer</span><br><span class="font-medium text-slate-800">{{ $partOut->jobOrder->customer->business_name ?: $partOut->jobOrder->customer->name }}</span></div>
                                    <div><span class="text-brand-600">Device</span><br><span class="font-medium text-slate-800">{{ $partOut->jobOrder->device?->label() ?: '—' }}</span></div>
                                    <div><span class="text-brand-600">Technician</span><br><span class="font-medium text-slate-800">{{ $partOut->jobOrder->technician?->user?->name ?? '—' }}</span></div>
                                    <div><span class="text-brand-600">Job Status</span><br><x-badge :tone="$partOut->jobOrder->statusTone()">{{ $partOut->jobOrder->statusLabel() }}</x-badge></div>
                                </div>

                                <table class="mt-3 w-full table-fixed border-t border-slate-200 pt-3 text-xs">
                                    <thead>
                                        <tr class="text-slate-400">
                                            <th class="w-[46%] py-1 text-left font-medium">Product</th>
                                            <th class="w-[12%] py-1 text-right font-medium">Qty</th>
                                            <th class="w-[21%] py-1 text-right font-medium">Unit Cost</th>
                                            <th class="w-[21%] py-1 text-right font-medium">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($partOut->items as $item)
                                            <tr>
                                                <td class="py-1.5 text-slate-600">{{ $item->product_name }}</td>
                                                <td class="py-1.5 text-right text-slate-600">{{ $item->quantity }}</td>
                                                <td class="py-1.5 text-right text-slate-600">{{ $peso($item->unit_price) }}</td>
                                                <td class="py-1.5 text-right font-medium text-slate-700">{{ $peso($item->line_total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p class="mt-2 text-xs text-slate-400">
                                    {{ $partOut->code }}
                                    @if ($partOut->notes) &middot; {{ $partOut->notes }} @endif
                                    &middot; Issued by {{ $partOut->issuedBy?->name ?? 'Unknown' }}
                                </p>

                                @if ($isAdmin && $partOut->status === 'pending_approval')
                                    <div class="mt-3 flex items-center justify-end gap-2 border-t border-amber-200 pt-3">
                                        <form method="POST" action="{{ route('parts-out.approve', $partOut) }}" onsubmit="return confirm('Approve {{ $partOut->code }}? This will deduct stock and add {{ $peso($partOut->total_cost) }} to the job order total.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                Accept Request
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('parts-out.reject', $partOut) }}" onsubmit="return PartsOut.submitReject(this, '{{ $partOut->code }}')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="reason" class="po-reject-reason">
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                Reject Request
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="py-14 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            <p class="mt-2 text-sm text-slate-400">No parts out records match your filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($partOuts->hasPages())
        <div class="mt-4 border-t border-slate-100 pt-4">
            {{ $partOuts->links() }}
        </div>
    @endif
</div>

{{-- Record Parts Out modal --}}
<div id="parts-out-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('parts-out-modal')">
    <div class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex shrink-0 items-center justify-between px-6 py-3">
            <h2 class="text-base font-semibold text-white">Record Parts Out</h2>
            <button type="button" onclick="closeModal('parts-out-modal')" class="text-white/60 hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="parts-out-form" method="POST" action="{{ route('parts-out.store') }}" class="min-h-0 flex-1 space-y-4 overflow-y-scroll px-6 py-4">
            @csrf
            <div id="po-item-inputs"></div>

            @if ($errors->any())
                <div class="rounded-lg border border-rose-400/40 bg-rose-900/30 px-3.5 py-2.5 text-xs text-rose-200">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="po-job-order" class="block text-sm font-medium text-brand-100">Job Order *</label>
                <select id="po-job-order" name="job_order_id" required onchange="PartsOut.setJobOrder(this)"
                    class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <option value="">Select a job order&hellip;</option>
                    @foreach ($jobOrders as $jo)
                        <option value="{{ $jo->id }}"
                            data-customer="{{ $jo->customer->business_name ?: $jo->customer->name }}"
                            data-device="{{ $jo->device?->label() ?: '—' }}"
                            data-technician="{{ $jo->technician?->user?->name ?? '—' }}"
                            data-status-label="{{ $jo->statusLabel() }}"
                            data-status-tone="{{ $jo->statusTone() }}">
                            {{ $jo->code }} &mdash; {{ $jo->customer->business_name ?: $jo->customer->name }} &middot; {{ $jo->service->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="po-context" class="hidden grid-cols-2 gap-x-4 gap-y-2 rounded-lg border border-brand-600 bg-brand-900/40 px-3.5 py-3 text-sm">
                <div><span class="text-brand-300">Customer</span><br><span id="po-ctx-customer" class="font-medium text-white"></span></div>
                <div><span class="text-brand-300">Device</span><br><span id="po-ctx-device" class="font-medium text-white"></span></div>
                <div><span class="text-brand-300">Technician</span><br><span id="po-ctx-technician" class="font-medium text-white"></span></div>
                <div><span class="text-brand-300">Job Status</span><br><span id="po-ctx-status" class="mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"></span></div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-300">Parts Used</span>
                    <button type="button" onclick="PartsOut.addRow()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-brand-500 px-2.5 py-1.5 text-xs font-medium text-brand-100 hover:bg-brand-700/40">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add Part
                    </button>
                </div>
                <div id="po-rows" class="mt-2.5 space-y-2"></div>
            </div>

            <div>
                <label for="po-notes" class="block text-sm font-medium text-brand-100">Notes (optional)</label>
                <textarea id="po-notes" name="notes" rows="2" placeholder="e.g. Installed during hardware upgrade"
                    class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-400"></textarea>
            </div>

            <div class="flex items-center justify-between border-t border-brand-600 pt-3">
                <span class="text-sm text-brand-100">Total Parts Cost</span>
                <span id="po-total" class="text-lg font-semibold text-white">₱0.00</span>
            </div>
        </form>
        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-brand-600 px-6 py-3">
            <button type="button" onclick="closeModal('parts-out-modal')" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
            <button type="submit" form="parts-out-form" id="po-submit" disabled
                class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-400 disabled:cursor-not-allowed disabled:bg-brand-600/50 disabled:text-brand-200">Issue Parts</button>
        </div>
    </div>
</div>

<script>
    const PO_TONES = {
        amber: 'bg-amber-100 text-amber-700',
        brand: 'bg-brand-100 text-brand-700',
        teal: 'bg-teal-100 text-teal-700',
        rose: 'bg-rose-100 text-rose-700',
        indigo: 'bg-indigo-100 text-indigo-700',
        violet: 'bg-violet-100 text-violet-700',
        slate: 'bg-slate-100 text-slate-600',
    };

    const PartsOut = {
        products: @json($partOutProducts),
        rows: [],
        nextRowId: 1,
        jobOrderId: '',

        peso(v) { return '₱' + (Number(v) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        open() {
            this.rows = [];
            this.nextRowId = 1;
            this.jobOrderId = '';
            document.getElementById('po-job-order').value = '';
            document.getElementById('po-notes').value = '';
            document.getElementById('po-context').classList.add('hidden');
            this.addRow();
            openModal('parts-out-modal');
        },

        setJobOrder(sel) {
            this.jobOrderId = sel.value;
            const opt = sel.options[sel.selectedIndex];
            const has = !!sel.value;
            document.getElementById('po-context').classList.toggle('hidden', !has);
            document.getElementById('po-context').classList.toggle('grid', has);
            if (has) {
                document.getElementById('po-ctx-customer').textContent = opt.dataset.customer || '—';
                document.getElementById('po-ctx-device').textContent = opt.dataset.device || '—';
                document.getElementById('po-ctx-technician').textContent = opt.dataset.technician || '—';
                const statusEl = document.getElementById('po-ctx-status');
                statusEl.textContent = opt.dataset.statusLabel || '';
                statusEl.className = 'mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ' + (PO_TONES[opt.dataset.statusTone] || PO_TONES.slate);
            }
            this.updateSubmitState();
        },

        addRow() {
            this.rows.push({ id: this.nextRowId++, productId: '', qty: 1 });
            this.render();
        },

        removeRow(id) {
            this.rows = this.rows.filter((r) => r.id !== id);
            this.render();
        },

        setRowProduct(id, productId) {
            const row = this.rows.find((r) => r.id === id);
            if (row) row.productId = productId;
            this.render();
        },

        setRowQty(id, qty) {
            const row = this.rows.find((r) => r.id === id);
            if (row) row.qty = Math.max(1, parseInt(qty, 10) || 1);
            this.render();
        },

        total() {
            return this.rows.reduce((sum, r) => {
                const p = this.products[r.productId];
                return sum + (p ? p.price * r.qty : 0);
            }, 0);
        },

        render() {
            const container = document.getElementById('po-rows');
            container.innerHTML = this.rows.map((r) => {
                const p = this.products[r.productId];
                const options = ['<option value="">Select a part…</option>'].concat(
                    Object.values(this.products).map((prod) =>
                        `<option value="${prod.id}" ${String(prod.id) === String(r.productId) ? 'selected' : ''}>${prod.code} — ${prod.name}</option>`)
                ).join('');
                let stockBadge = '';
                if (p) {
                    if (p.stock <= 0) {
                        stockBadge = `<span class="mt-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Stock: 0</span>`;
                    } else if (p.stock <= 5) {
                        stockBadge = `<span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Stock: ${p.stock} · Low</span>`;
                    } else {
                        stockBadge = `<span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Stock: ${p.stock}</span>`;
                    }
                }
                const warning = (p && r.qty > p.stock)
                    ? `<p class="mt-1 text-xs text-rose-300">Only ${p.stock} in stock — issuing will backorder ${r.qty - p.stock}.</p>`
                    : '';
                const lineTotal = p ? p.price * r.qty : 0;

                return `
                    <div class="rounded-lg bg-white p-3">
                        <div class="grid grid-cols-[1fr_64px_88px_92px_28px] items-start gap-2">
                            <div>
                                <select onchange="PartsOut.setRowProduct(${r.id}, this.value)" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">${options}</select>
                                ${stockBadge}
                            </div>
                            <input type="number" min="1" value="${r.qty}" onchange="PartsOut.setRowQty(${r.id}, this.value)" class="rounded-lg border border-slate-200 px-2 py-1.5 text-center text-sm text-slate-700">
                            <div class="pt-1.5 text-right text-sm text-slate-500">${p ? this.peso(p.price) : '—'}</div>
                            <div class="pt-1.5 text-right text-sm font-semibold text-slate-800">${p ? this.peso(lineTotal) : '—'}</div>
                            <button type="button" onclick="PartsOut.removeRow(${r.id})" class="pt-1 text-slate-300 hover:text-rose-500" aria-label="Remove part">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        ${warning}
                    </div>`;
            }).join('');

            document.getElementById('po-item-inputs').innerHTML = this.rows
                .filter((r) => r.productId)
                .map((r, i) => `
                    <input type="hidden" name="items[${i}][product_id]" value="${r.productId}">
                    <input type="hidden" name="items[${i}][quantity]" value="${r.qty}">`).join('');

            document.getElementById('po-total').textContent = this.peso(this.total());
            this.updateSubmitState();
        },

        updateSubmitState() {
            const ready = !!this.jobOrderId && this.rows.some((r) => r.productId);
            document.getElementById('po-submit').disabled = !ready;
        },

        submitReject(form, code) {
            const reason = prompt(`Reason for rejecting ${code}:`);
            if (!reason || !reason.trim()) return false;
            form.querySelector('.po-reject-reason').value = reason.trim();
            return true;
        },
    };

    function openPartsOutModal() {
        PartsOut.open();
    }

    function togglePartOutRow(id) {
        const row = document.getElementById('po-detail-' + id);
        const chevron = document.getElementById('po-chevron-' + id);
        row.classList.toggle('hidden');
        chevron.classList.toggle('rotate-90');
    }

    @if ($errors->any() && old('job_order_id') !== null)
        document.addEventListener('DOMContentLoaded', function () {
            openPartsOutModal();
            document.getElementById('po-job-order').value = @json(old('job_order_id'));
            PartsOut.setJobOrder(document.getElementById('po-job-order'));
        });
    @endif
</script>
