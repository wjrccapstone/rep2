@php
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
@endphp

@if (session('status'))
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form id="pos-form" method="POST" action="{{ route('cashier.sale.store') }}">
    @csrf
    <input type="hidden" name="sale_type" id="pos-sale-type" value="walk_in">
    <div id="pos-item-inputs"></div>
    <div id="pos-part-out-inputs"></div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_370px]">
        {{-- LEFT: sale type + products + forecast dataset --}}
        <div class="space-y-6">
            {{-- Sale type --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Transaction Type</h2>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" id="pos-mode-product" onclick="POS.setMode('product')"
                        class="pos-mode-btn flex items-center justify-center gap-2 rounded-xl border-2 px-4 py-3.5 text-sm font-semibold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272" /></svg>
                        Product Sale
                    </button>
                    <button type="button" id="pos-mode-job" onclick="POS.setMode('job_order')"
                        class="pos-mode-btn flex items-center justify-center gap-2 rounded-xl border-2 px-4 py-3.5 text-sm font-semibold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V3.75a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v15.75a1.5 1.5 0 0 0 1.5 1.5Z" /></svg>
                        Job Order Bill
                    </button>
                </div>

                <div id="pos-job-order-panel" class="mt-4 hidden">
                    <label class="text-xs font-medium text-slate-500">Job order to bill</label>
                    @if ($billableJobOrders->isEmpty())
                        <p class="mt-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-400">No partially paid job orders right now.</p>
                    @else
                        <div class="mt-1.5 max-h-80 overflow-y-auto rounded-lg ring-1 ring-slate-100">
                            <table class="w-full text-left text-xs">
                                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="w-8 px-3 py-2.5"></th>
                                        <th class="px-3 py-2.5">Job Order</th>
                                        <th class="px-3 py-2.5">Customer</th>
                                        <th class="px-3 py-2.5">Service</th>
                                        <th class="px-3 py-2.5">Device</th>
                                        <th class="px-3 py-2.5">Status</th>
                                        <th class="px-3 py-2.5 text-right">Bill</th>
                                    </tr>
                                </thead>
                                <tbody id="pos-job-order-rows" class="divide-y divide-slate-100 bg-white">
                                    @foreach ($billableJobOrders as $jo)
                                        @php
                                            $joPaid = (float) ($jo->paid_total ?? 0);
                                            $joBalance = max(0, round((float) $jo->cost - $joPaid, 2));
                                            // Parts already approved for this job but not yet billed — shown as
                                            // their own read-only lines in the cart when this row is picked.
                                            $joParts = $jo->partOuts->flatMap->items->map(fn ($item) => [
                                                'name' => $item->product_name,
                                                'qty' => $item->quantity,
                                                'price' => (float) $item->unit_price,
                                                'total' => (float) $item->line_total,
                                            ])->values();
                                            $joPartOutIds = $jo->partOuts->pluck('id')->values();
                                        @endphp
                                        <tr data-jo-id="{{ $jo->id }}" data-cost="{{ (float) $jo->cost }}" data-balance="{{ $joBalance }}" data-paid="{{ $joPaid }}" data-code="{{ $jo->code }}"
                                            data-parts="{{ json_encode($joParts) }}" data-part-out-ids="{{ json_encode($joPartOutIds) }}"
                                            onclick="POS.selectJobOrder(this)"
                                            class="pos-jo-row cursor-pointer transition hover:bg-brand-50/60">
                                            <td class="px-3 py-2.5">
                                                <input type="radio" name="pos_job_order_pick" value="{{ $jo->id }}" tabindex="-1"
                                                    class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2.5 font-semibold text-slate-700">{{ $jo->code }}</td>
                                            <td class="px-3 py-2.5 text-slate-600">{{ $jo->customer->business_name ?: $jo->customer->name }}</td>
                                            <td class="px-3 py-2.5 text-slate-600">{{ $jo->service->name }}</td>
                                            <td class="px-3 py-2.5 text-slate-600">{{ $jo->device?->label() ?: '—' }}</td>
                                            <td class="px-3 py-2.5"><x-badge :tone="$jo->statusTone()">{{ $jo->statusLabel() }}</x-badge></td>
                                            <td class="px-3 py-2.5 text-right font-semibold text-slate-700">
                                                {{ $peso($joBalance) }}
                                                @if ($joPaid > 0)
                                                    <span class="block text-[10px] font-normal text-slate-400">{{ $peso($joPaid) }} of {{ $peso($jo->cost) }} paid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <p id="pos-job-order-selected" class="mt-2 hidden rounded-lg bg-brand-50 px-3 py-2 text-xs font-medium text-brand-700"></p>
                    <input type="hidden" name="job_order_id" id="pos-job-order-id">
                </div>
            </section>

            {{-- Products --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Products</h2>

                <div class="relative mt-3">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                    <input type="text" id="pos-search" oninput="POS.applyFilters()" placeholder="Search product name or ID"
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400/30">
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" data-cat="" onclick="POS.setCategory('')"
                        class="pos-cat-btn rounded-full px-3.5 py-1.5 text-xs font-semibold transition">All</button>
                    @foreach ($categories as $category)
                        <button type="button" data-cat="{{ $category }}" onclick="POS.setCategory({{ Illuminate\Support\Js::from($category) }})"
                            class="pos-cat-btn rounded-full px-3.5 py-1.5 text-xs font-semibold transition">{{ $category }}</button>
                    @endforeach
                </div>

                <div id="pos-product-grid" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @forelse ($products as $product)
                        @php
                            $soldOut = $product->stock <= 0;
                            $low = ! $soldOut && $product->stock <= \App\Models\Product::LOW_STOCK_THRESHOLD;
                        @endphp
                        <button type="button"
                            data-name="{{ \Illuminate\Support\Str::lower($product->name) }}"
                            data-code="{{ \Illuminate\Support\Str::lower($product->code) }}"
                            data-category="{{ $product->category->name }}"
                            @if (! $soldOut) onclick="POS.add({{ $product->id }})" @else disabled aria-disabled="true" @endif
                            class="pos-product-card group relative flex min-h-[128px] flex-col rounded-xl border p-3 text-left transition
                                {{ $soldOut
                                    ? 'cursor-not-allowed border-slate-100 bg-slate-50'
                                    : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-brand-400 hover:shadow-md' }}">
                            <div class="h-20 w-full shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-1.5 {{ $soldOut ? 'opacity-50 grayscale' : '' }}">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-300">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                    </span>
                                @endif
                            </div>
                            <span class="mt-2 font-mono text-[10px] font-medium {{ $soldOut ? 'text-slate-300' : 'text-slate-400' }}">{{ $product->code }}</span>
                            <span class="mt-1 line-clamp-2 text-sm font-semibold leading-snug {{ $soldOut ? 'text-slate-400' : 'text-slate-800' }}">{{ $product->name }}</span>
                            <span class="mt-auto pt-2 text-[11px] font-medium
                                {{ $soldOut ? 'text-rose-400' : ($low ? 'text-amber-600' : 'text-slate-400') }}">
                                @if ($soldOut)
                                    Not available
                                @elseif ($low)
                                    {{ $product->stock }} left · reorder
                                @else
                                    {{ $product->stock }} on hand
                                @endif
                            </span>
                            <span class="mt-1 text-sm font-bold {{ $soldOut ? 'text-slate-400' : 'text-slate-900' }}">{{ $peso($product->price) }}</span>
                            <span data-qty-badge="{{ $product->id }}" class="absolute right-2 top-2 hidden h-5 min-w-[1.25rem] rounded-full bg-brand-600 px-1 text-center text-[11px] font-bold leading-5 text-white"></span>
                        </button>
                    @empty
                        <p class="col-span-full py-10 text-center text-sm text-slate-400">No active products in the catalog.</p>
                    @endforelse
                </div>
                <p id="pos-no-results" class="hidden py-10 text-center text-sm text-slate-400">No products match your search.</p>
            </section>

            {{-- Forecast dataset --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" onclick="POS.toggleForecast()" class="flex min-w-0 items-center gap-2 text-left">
                        <svg id="pos-forecast-chevron" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        <span>
                            <h2 class="text-base font-semibold text-slate-800">Forecast dataset</h2>
                            <p class="font-mono text-[10px] text-slate-400">sales_summary · revenue_summary</p>
                        </span>
                    </button>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('cashier.dataset.export') }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            Export CSV
                        </a>
                        <button type="button" onclick="POS.toggleForecast()" id="pos-forecast-toggle"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Show
                        </button>
                    </div>
                </div>

                <div id="pos-forecast-body" class="hidden">
                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-3.5">
                        <p class="text-[11px] font-medium text-slate-500">Sales this month</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ $peso($dataset['totalSalesThisMonth']) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3.5">
                        <p class="text-[11px] font-medium text-slate-500">Units this month</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($dataset['productCountThisMonth']) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3.5">
                        <p class="text-[11px] font-medium text-slate-500">Revenue this month</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ $peso($dataset['totalRevenueThisMonth']) }}</p>
                    </div>
                    <div class="rounded-xl bg-brand-50 p-3.5 ring-1 ring-brand-100">
                        <p class="text-[11px] font-medium text-brand-600">Next month (SARIMA)</p>
                        <p class="mt-1 text-lg font-bold text-brand-700">{{ $peso($dataset['nextMonthRevenue']) }}</p>
                    </div>
                </div>

                @php $chartMax = max(1, collect($dataset['chart'])->max('value')); @endphp
                <div class="mt-5 overflow-x-auto pb-1">
                    <div class="relative min-w-[560px]">
                        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-44 flex-col justify-between">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="border-t border-dashed border-slate-100"></div>
                            @endfor
                        </div>
                        <div class="relative flex h-44 items-end gap-1.5">
                            @foreach ($dataset['chart'] as $bar)
                                @php
                                    $h = max(2, round(($bar['value'] / $chartMax) * 100));
                                    $tone = match ($bar['tone']) {
                                        'live' => 'bg-amber-400',
                                        'forecast' => 'bg-slate-300',
                                        default => 'bg-brand-500',
                                    };
                                @endphp
                                <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                                    <div class="pointer-events-none absolute -top-8 z-10 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-[11px] font-medium text-white opacity-0 shadow transition-opacity group-hover:opacity-100">{{ $bar['display'] }}</div>
                                    <div class="w-full max-w-[24px] rounded-t {{ $tone }} min-h-[3px] transition-all group-hover:brightness-110" style="height: {{ $h }}%"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-2 flex min-w-[560px] gap-1.5">
                        @foreach ($dataset['chart'] as $bar)
                            <div class="flex-1 text-center text-[10px] font-medium text-slate-400">{{ $bar['label'] }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span> Recorded product sales</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-400"></span> Current month (live)</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-slate-300"></span> SARIMA forecast{{ $dataset['modelOrder'] ? ' · '.$dataset['modelOrder'] : '' }}</span>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl ring-1 ring-slate-100">
                    <table class="w-full min-w-[760px] text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th class="whitespace-nowrap px-3 py-2.5">sales_ID</th>
                                <th class="whitespace-nowrap px-3 py-2.5">year</th>
                                <th class="whitespace-nowrap px-3 py-2.5">month</th>
                                <th class="whitespace-nowrap px-3 py-2.5">month_name</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">total_sales</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">product_count</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">avg_sales</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">total_revenue</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">job_count</th>
                                <th class="whitespace-nowrap px-3 py-2.5 text-right">avg_ticket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($dataset['rows'] as $row)
                                <tr class="text-slate-600 hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-700">{{ $row['sales_id'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $row['year'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $row['month'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $row['month_name'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['total_sales'], 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['product_count']) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['avg_sales'], 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['total_revenue'], 2) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['job_count']) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ number_format($row['avg_ticket'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </section>
        </div>

        {{-- RIGHT: current sale --}}
        <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <div class="flex items-center justify-center gap-4 rounded-xl bg-brand-900 px-4 py-3 text-white shadow-sm">
                <div class="text-center">
                    <p class="text-[10px] font-medium uppercase tracking-wide text-brand-100/70">Sales today</p>
                    <p class="text-sm font-semibold">{{ $peso($salesTodayTotal) }}</p>
                </div>
                <div class="h-8 w-px bg-white/15"></div>
                <div class="text-center">
                    <p class="text-[10px] font-medium uppercase tracking-wide text-brand-100/70">Transactions</p>
                    <p class="text-sm font-semibold">{{ $transactionsToday }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-semibold text-slate-800">Current sale</h2>
                </div>

                {{-- Line items --}}
                <div class="min-h-[120px] py-1">
                    <p id="pos-empty" class="flex h-[112px] flex-col items-center justify-center gap-2 text-center text-sm text-slate-400">
                        <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                        No items yet — tap a product to start
                    </p>
                    <ul id="pos-lines" class="hidden divide-y divide-slate-100"></ul>
                </div>

                <dl class="space-y-1.5 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Items</dt><dd id="pos-count" class="font-semibold text-slate-700">0</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd id="pos-gross" class="font-semibold text-slate-700">₱0.00</dd></div>
                    <div id="pos-parts-row" class="hidden justify-between"><dt class="text-slate-500">Parts (job order)</dt><dd id="pos-parts" class="font-semibold text-slate-700">₱0.00</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Labor cost</dt><dd id="pos-labor" class="font-semibold text-slate-700">₱0.00</dd></div>
                </dl>

                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2.5 ring-1 ring-amber-100">
                    <div class="flex items-center justify-between">
                        <span id="pos-total-label" class="text-sm font-semibold text-slate-700">Total amount</span>
                        <span id="pos-total" class="text-2xl font-extrabold text-amber-600">₱0.00</span>
                    </div>
                    <p id="pos-total-sub" class="mt-1 hidden text-right text-[11px] font-medium text-slate-500"></p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <div>
                        <label for="pos-payment" class="text-xs font-medium text-slate-500">Payment method</label>
                        <select name="payment_method" id="pos-payment"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @foreach (\App\Models\Sale::PAYMENT_METHODS as $method)
                                <option value="{{ $method }}" @selected($method === 'Cash')>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="pos-cash-block" class="grid grid-cols-1 gap-3">
                        <div>
                            <label for="pos-tendered" class="text-xs font-medium text-slate-500">Cash tendered</label>
                            <input type="number" step="0.01" min="0" name="amount_tendered" id="pos-tendered" value="" placeholder="0.00"
                                oninput="POS.render()"
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <div class="mt-2 grid grid-cols-3 gap-1.5">
                                @foreach ([100, 500, 1000, 5000] as $amt)
                                    <button type="button" onclick="POS.quickCash({{ $amt }})"
                                        class="rounded-md border border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-600 hover:border-brand-300 hover:bg-brand-50">₱{{ number_format($amt) }}</button>
                                @endforeach
                                <button type="button" onclick="POS.quickCash('exact')"
                                    class="col-span-2 rounded-md border border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-600 hover:border-brand-300 hover:bg-brand-50">Exact amount</button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 ring-1 ring-emerald-100">
                            <span class="text-sm font-medium text-slate-600">Change</span>
                            <span id="pos-change" class="text-base font-bold text-emerald-600">₱0.00</span>
                        </div>
                    </div>

                    <div id="pos-payment-status-block" class="hidden">
                        <label for="pos-ispaid" class="text-xs font-medium text-slate-500">Payment status</label>
                        <select name="is_paid" id="pos-ispaid" onchange="POS.setPaid(this.value)"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <option value="1">Paid — fully settled</option>
                            <option value="0">Unpaid — partial / on account</option>
                        </select>

                        {{-- Shown only when "Unpaid — partial" is selected: how much the customer pays now.
                             What they pay is subtracted from the total above, which then shows the balance. --}}
                        <div id="pos-partial-block" class="mt-2 hidden rounded-lg border border-amber-200 bg-amber-50/60 p-3">
                            <label for="pos-amount-paid" class="text-xs font-medium text-slate-600">Amount paid now</label>
                            <input type="number" step="0.01" min="0" name="amount_paid" id="pos-amount-paid" value="" placeholder="0.00"
                                oninput="POS.render()"
                                class="mt-1 block w-full rounded-lg border border-amber-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400/40">
                            <div class="mt-2 flex gap-1.5">
                                <button type="button" onclick="POS.payHalf()"
                                    class="rounded-md border border-amber-300 bg-white px-2 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">Half</button>
                                <button type="button" onclick="POS.payFull()"
                                    class="rounded-md border border-amber-300 bg-white px-2 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">Pay full</button>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">This is deducted from the total — the amount above becomes the remaining balance.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="button" onclick="POS.clear()"
                        class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Clear</button>
                    <button type="submit" id="pos-complete" disabled
                        class="flex-1 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none">Complete sale</button>
                </div>
            </div>
        </aside>
    </div>
</form>

<script>
    const POS = {
        products: @json($posProducts),
        category: '',
        items: {},        // productId -> qty
        mode: 'product',  // 'product' | 'job_order' — which panel is active
        jobOrderId: '',
        laborCost: 0,
        jobOrderParts: [],     // parts already approved for the picked job order, read-only in the cart
        jobOrderPartOutIds: [],
        paid: true,

        peso(v) { return '₱' + (Number(v) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        // Toggles which transaction-type panel is shown. Switching back to a plain
        // product sale clears any job order that was picked.
        setMode(mode) {
            this.mode = mode;
            document.getElementById('pos-mode-product').classList.toggle('is-active', mode === 'product');
            document.getElementById('pos-mode-job').classList.toggle('is-active', mode === 'job_order');
            document.querySelectorAll('.pos-mode-btn').forEach((b) => {
                const active = b.classList.contains('is-active');
                b.classList.toggle('border-brand-600', active);
                b.classList.toggle('bg-brand-900', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('border-slate-200', !active);
                b.classList.toggle('bg-white', !active);
                b.classList.toggle('text-slate-600', !active);
            });
            document.getElementById('pos-job-order-panel').classList.toggle('hidden', mode !== 'job_order');

            // A plain product sale is always paid in full on the spot — there's no
            // "on account" retail sale — so the payment-status choice only makes sense
            // once a job order (which can carry a running balance) is being billed.
            document.getElementById('pos-payment-status-block').classList.toggle('hidden', mode !== 'job_order');

            if (mode === 'product') {
                this.jobOrderId = '';
                this.laborCost = 0;
                this.jobOrderParts = [];
                this.jobOrderPartOutIds = [];
                document.getElementById('pos-sale-type').value = 'walk_in';
                document.getElementById('pos-job-order-id').value = '';
                document.getElementById('pos-part-out-inputs').innerHTML = '';
                document.getElementById('pos-job-order-selected').classList.add('hidden');
                document.querySelectorAll('.pos-jo-row').forEach((r) => r.classList.remove('bg-brand-50', 'ring-1', 'ring-brand-300'));
                document.querySelectorAll('input[name="pos_job_order_pick"]').forEach((r) => { r.checked = false; });

                document.getElementById('pos-ispaid').value = '1';
                document.getElementById('pos-amount-paid').value = '';
                this.paid = true;
            }
            this.render();
        },

        // Picking a job order here bills its outstanding balance as labor cost;
        // products can still be added on top of it in the same transaction. Any parts
        // an admin already approved for this job (not yet billed) ride along as their
        // own read-only lines in the cart — same cost that's already folded into the
        // balance below, just shown so the cashier can see what it's made of.
        selectJobOrder(row) {
            document.querySelectorAll('.pos-jo-row').forEach((r) => r.classList.remove('bg-brand-50', 'ring-1', 'ring-brand-300'));
            row.classList.add('bg-brand-50', 'ring-1', 'ring-brand-300');
            const radio = row.querySelector('input[name="pos_job_order_pick"]');
            if (radio) radio.checked = true;

            this.jobOrderId = row.dataset.joId;
            this.laborCost = parseFloat(row.dataset.balance || row.dataset.cost || 0);
            this.jobOrderParts = JSON.parse(row.dataset.parts || '[]');
            this.jobOrderPartOutIds = JSON.parse(row.dataset.partOutIds || '[]');
            document.getElementById('pos-sale-type').value = 'job_order';
            document.getElementById('pos-job-order-id').value = this.jobOrderId;
            document.getElementById('pos-part-out-inputs').innerHTML = this.jobOrderPartOutIds
                .map((id) => `<input type="hidden" name="part_out_ids[]" value="${id}">`).join('');

            const selected = document.getElementById('pos-job-order-selected');
            selected.textContent = 'Selected: ' + row.dataset.code + ' — bill ' + this.peso(this.laborCost);
            selected.classList.remove('hidden');

            this.render();
        },

        setCategory(cat) {
            this.category = cat;
            document.querySelectorAll('.pos-cat-btn').forEach((b) => {
                const active = b.dataset.cat === cat;
                b.classList.toggle('bg-brand-600', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('bg-slate-100', !active);
                b.classList.toggle('text-slate-600', !active);
                b.classList.toggle('hover:bg-slate-200', !active);
            });
            this.applyFilters();
        },

        applyFilters() {
            const q = (document.getElementById('pos-search').value || '').toLowerCase().trim();
            let shown = 0;
            document.querySelectorAll('.pos-product-card').forEach((card) => {
                const matchQ = !q || card.dataset.name.includes(q) || card.dataset.code.includes(q);
                const matchCat = !this.category || card.dataset.category === this.category;
                const visible = matchQ && matchCat;
                card.classList.toggle('hidden', !visible);
                if (visible) shown++;
            });
            document.getElementById('pos-no-results').classList.toggle('hidden', shown > 0);
        },

        add(id) {
            const p = this.products[id];
            if (!p) return;
            const current = this.items[id] || 0;
            if (current + 1 > p.stock) return;
            this.items[id] = current + 1;
            this.render();
        },

        setQty(id, qty) {
            const p = this.products[id];
            qty = Math.max(0, Math.min(parseInt(qty, 10) || 0, p ? p.stock : 0));
            if (qty <= 0) delete this.items[id];
            else this.items[id] = qty;
            this.render();
        },

        remove(id) { delete this.items[id]; this.render(); },

        setPaid(v) {
            this.paid = String(v) === '1';
            // Keep only the active block's inputs, so a stale value from the hidden
            // block never gets submitted.
            if (this.paid) {
                document.getElementById('pos-amount-paid').value = '';
            } else {
                document.getElementById('pos-tendered').value = '';
            }
            this.render();
        },

        quickCash(v) {
            const totals = this.totals();
            const field = document.getElementById('pos-tendered');
            field.value = v === 'exact' ? totals.total.toFixed(2) : Number(v).toFixed(2);
            this.render();
        },

        payHalf() {
            document.getElementById('pos-amount-paid').value = (this.totals().total / 2).toFixed(2);
            this.render();
        },

        payFull() {
            document.getElementById('pos-amount-paid').value = this.totals().total.toFixed(2);
            this.render();
        },

        clear() {
            this.items = {};
            this.paid = true;
            document.getElementById('pos-tendered').value = '';
            document.getElementById('pos-amount-paid').value = '';
            document.getElementById('pos-ispaid').value = '1';
            this.render();
        },

        entries() { return Object.entries(this.items).map(([id, qty]) => ({ p: this.products[id], qty })); },

        totals() {
            const itemsTotal = this.entries().reduce((s, { p, qty }) => s + (p ? p.price * qty : 0), 0);
            const labor = this.laborCost;
            const gross = Math.round((itemsTotal + labor) * 100) / 100;
            // The job order's balance already includes any approved parts cost — split
            // it out here purely for display, so the "Parts (job order)" line the
            // cashier sees actually adds up with the line items shown above it.
            const partsCost = Math.round((this.jobOrderParts || []).reduce((s, it) => s + (it.total || 0), 0) * 100) / 100;
            const laborOnly = Math.max(0, Math.round((labor - partsCost) * 100) / 100);
            return { gross, labor, laborOnly, partsCost, total: gross };
        },

        render() {
            const entries = this.entries();
            const parts = this.jobOrderParts || [];
            const t = this.totals();

            // Line list
            const list = document.getElementById('pos-lines');
            const empty = document.getElementById('pos-empty');
            if (entries.length === 0 && parts.length === 0) {
                list.classList.add('hidden');
                empty.classList.remove('hidden');
                list.innerHTML = '';
            } else {
                empty.classList.add('hidden');
                list.classList.remove('hidden');

                const partsHtml = parts.map((it) => `
                    <li class="flex items-center gap-2 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-700">${it.name}</p>
                            <p class="text-[11px] text-slate-400">Qty ${it.qty} · ${this.peso(it.price)} each · <span class="font-medium text-brand-600">from job order</span></p>
                        </div>
                        <span class="w-20 text-right text-sm font-semibold text-slate-700">${this.peso(it.total)}</span>
                        <span class="w-[26px] shrink-0" aria-hidden="true"></span>
                    </li>`).join('');

                const entriesHtml = entries.map(({ p, qty }) => `
                    <li class="flex items-center gap-2 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-700">${p.name}</p>
                            <p class="font-mono text-[11px] text-slate-400">${p.code} · ${this.peso(p.price)}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="POS.setQty(${p.id}, ${qty - 1})" class="h-7 w-7 rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50">−</button>
                            <input type="text" inputmode="numeric" value="${qty}" onchange="POS.setQty(${p.id}, this.value)"
                                class="h-7 w-10 rounded-md border border-slate-200 text-center text-xs text-slate-700">
                            <button type="button" onclick="POS.setQty(${p.id}, ${qty + 1})" class="h-7 w-7 rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50">+</button>
                        </div>
                        <span class="w-20 text-right text-sm font-semibold text-slate-700">${this.peso(p.price * qty)}</span>
                        <button type="button" onclick="POS.remove(${p.id})" class="rounded p-1 text-slate-300 hover:text-rose-500" aria-label="Remove ${p.name}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </li>`).join('');

                list.innerHTML = partsHtml + entriesHtml;
            }

            // Quantity badges on product cards
            document.querySelectorAll('[data-qty-badge]').forEach((b) => {
                const qty = this.items[b.dataset.qtyBadge];
                b.textContent = qty || '';
                b.classList.toggle('hidden', !qty);
            });

            // Hidden form inputs
            document.getElementById('pos-item-inputs').innerHTML = entries.map(({ p, qty }, i) => `
                <input type="hidden" name="items[${i}][product_id]" value="${p.id}">
                <input type="hidden" name="items[${i}][quantity]" value="${qty}">`).join('');

            // Summary
            const unitCount = entries.reduce((s, { qty }) => s + qty, 0);
            document.getElementById('pos-count').textContent = unitCount;
            document.getElementById('pos-gross').textContent = this.peso(t.gross);
            document.getElementById('pos-labor').textContent = this.peso(t.laborOnly);
            const partsRow = document.getElementById('pos-parts-row');
            partsRow.classList.toggle('hidden', t.partsCost <= 0);
            partsRow.classList.toggle('flex', t.partsCost > 0);
            document.getElementById('pos-parts').textContent = this.peso(t.partsCost);
            document.getElementById('pos-total').textContent = this.peso(t.total);

            // Payment: fully paid → cash tendered / change; partial → the amount paid is
            // subtracted from the total above, which then shows the remaining balance.
            document.getElementById('pos-cash-block').classList.toggle('hidden', !this.paid);
            document.getElementById('pos-partial-block').classList.toggle('hidden', this.paid);

            const totalLabel = document.getElementById('pos-total-label');
            const totalValue = document.getElementById('pos-total');
            const totalSub = document.getElementById('pos-total-sub');

            if (this.paid) {
                totalLabel.textContent = 'Total amount';
                totalValue.textContent = this.peso(t.total);
                totalSub.classList.add('hidden');

                const tendered = parseFloat(document.getElementById('pos-tendered').value || '0');
                const change = tendered > 0 ? Math.max(0, tendered - t.total) : 0;
                document.getElementById('pos-change').textContent = this.peso(change);
            } else {
                const paidNow = Math.max(0, Math.min(parseFloat(document.getElementById('pos-amount-paid').value || '0'), t.total));

                if (paidNow > 0) {
                    const balance = Math.max(0, Math.round((t.total - paidNow) * 100) / 100);
                    totalLabel.textContent = 'Balance due';
                    totalValue.textContent = this.peso(balance);
                    totalSub.textContent = 'Total ' + this.peso(t.total) + ' − paid ' + this.peso(paidNow);
                    totalSub.classList.remove('hidden');
                } else {
                    totalLabel.textContent = 'Total amount';
                    totalValue.textContent = this.peso(t.total);
                    totalSub.textContent = 'Unpaid — on account';
                    totalSub.classList.remove('hidden');
                }
            }

            // Complete button — a job order alone (0 products) can still be billed;
            // otherwise at least one product is required.
            const ready = this.jobOrderId ? true : entries.length > 0;
            document.getElementById('pos-complete').disabled = !ready;
        },

        // The forecast dataset panel is optional reading, not part of ringing up a
        // sale, so it starts collapsed and remembers the cashier's choice per browser.
        toggleForecast(forceOpen) {
            const body = document.getElementById('pos-forecast-body');
            const open = typeof forceOpen === 'boolean' ? forceOpen : body.classList.contains('hidden');
            body.classList.toggle('hidden', !open);
            document.getElementById('pos-forecast-chevron').classList.toggle('rotate-90', open);
            document.getElementById('pos-forecast-toggle').textContent = open ? 'Hide' : 'Show';
            try {
                localStorage.setItem('pos-forecast-open', open ? '1' : '0');
            } catch (e) { /* private mode / storage blocked — just skip remembering */ }
        },
    };

    POS.setCategory('');
    POS.setMode('product');
    POS.render();

    let forecastOpen = false;
    try {
        forecastOpen = localStorage.getItem('pos-forecast-open') === '1';
    } catch (e) { /* default to collapsed */ }
    POS.toggleForecast(forecastOpen);
</script>
