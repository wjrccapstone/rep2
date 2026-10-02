@php
    $isArchive = $tab === 'archive';
    $isSales = $tab === 'sales';
    $isCatalog = $tab === 'catalog';
    $isTransaction = $tab === 'transaction';
    $isPartsOut = $tab === 'parts-out';
    $tabMeta = [
        'catalog' => ['label' => 'Product Catalog', 'icon' => 'M6 6h.008v.008H6V6Zm1.5-3h9a2.25 2.25 0 0 1 2.25 2.25v2.086a2.25 2.25 0 0 1-.659 1.591l-8.828 8.828a2.25 2.25 0 0 1-3.182 0l-3.086-3.086a2.25 2.25 0 0 1 0-3.182l8.828-8.828A2.25 2.25 0 0 1 7.5 3Z'],
        'transaction' => ['label' => 'Transaction', 'icon' =>'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'],
        'parts-out' => ['label' => 'Parts Out', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5'],
        'sales' => ['label' => 'Sales Records', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'archive' => ['label' => 'Archive', 'icon' => 'M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4'],
    ];
@endphp
<x-app-layout title="Product Inventory">
    <x-page-header title="Product Inventory" subtitle="Manage stock, availability status, and 1-month product warranties.">
        <x-slot:actions>
            @unless ($isSales)
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-4 py-2 text-xs font-medium text-brand-700 shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    1-month product warranty
                </span>
            @endunless
            @if ($isCatalog && auth()->user()->role !== 'technician')
                <a href="{{ route('products.export', ['tab' => $tab]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export CSV
                </a>
                <form id="import-csv-form" method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="inline-block">
                    @csrf
                    <label for="import-csv-input" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                        Import CSV
                    </label>
                    <input type="file" id="import-csv-input" name="file" accept=".csv,text/csv" class="hidden" onchange="this.form.submit()">
                </form>
                <button type="button" onclick="openProductCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Product
                </button>
            @endif
            @if ($isPartsOut)
                <a href="{{ route('parts-out.export') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export CSV
                </a>
                {{-- Admin's job here is to accept/reject technician requests, not record them. --}}
                @unless (auth()->user()->role === 'admin')
                    <button type="button" onclick="openPartsOutModal()"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Record Parts Out
                    </button>
                @endunless
            @endif
            @if ($isSales)
                <a href="{{ route('sales.export') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export CSV
                </a>
                <form id="import-sales-csv-form" method="POST" action="{{ route('sales.import') }}" enctype="multipart/form-data" class="inline-block">
                    @csrf
                    <label for="import-sales-csv-input" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                        Import CSV
                    </label>
                    <input type="file" id="import-sales-csv-input" name="file" accept=".csv,text/csv" class="hidden" onchange="this.form.submit()">
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex items-center gap-6 border-b border-slate-200">
        @foreach ($tabMeta as $key => $meta)
            @continue(! in_array($key, $allowedTabs, true))
            <a href="{{ route('products.index', ['tab' => $key]) }}"
                class="flex items-center gap-1.5 border-b-2 px-1 pb-3 text-sm font-medium {{ $tab === $key ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}" /></svg>
                {{ $meta['label'] }}
                @if ($tabCounts[$key] > 0)
                    <span class="rounded-full px-1.5 py-0.5 text-xs font-semibold {{ $tab === $key ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">{{ $tabCounts[$key] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if ($isTransaction)
        @include('cashier._form', $pos)
    @elseif ($isPartsOut)
        @include('products._parts-out', $partsOut)
    @elseif ($isSales)
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <p class="text-xs font-medium text-slate-400">Total Sales Records</p>
                <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $saleStats['total'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <p class="text-xs font-medium text-slate-400">Total Revenue</p>
                <p class="mt-1 text-2xl font-semibold text-teal-600">₱{{ number_format($saleStats['revenue'], 0) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <p class="text-xs font-medium text-slate-400">Warranty Active</p>
                <p class="mt-1 text-2xl font-semibold text-brand-600">{{ $saleStats['warrantyActive'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <p class="text-xs font-medium text-slate-400">Warranty Expired</p>
                <p class="mt-1 text-2xl font-semibold text-slate-400">{{ $saleStats['warrantyExpired'] }}</p>
            </div>
        </div>

        <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="hidden" name="tab" value="sales">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by ID, product, or payment..."
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                </div>
                <div class="flex overflow-hidden rounded-lg border border-slate-200">
                    <a href="{{ route('products.index', ['tab' => 'sales', 'q' => $filters['q']]) }}"
                        class="px-4 py-2.5 text-sm font-medium {{ $filters['warranty'] === '' ? 'bg-brand-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">All</a>
                    <a href="{{ route('products.index', ['tab' => 'sales', 'q' => $filters['q'], 'warranty' => 'active']) }}"
                        class="border-l border-slate-200 px-4 py-2.5 text-sm font-medium {{ $filters['warranty'] === 'active' ? 'bg-brand-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">Active ({{ $saleStats['warrantyActive'] }})</a>
                    <a href="{{ route('products.index', ['tab' => 'sales', 'q' => $filters['q'], 'warranty' => 'expired']) }}"
                        class="border-l border-slate-200 px-4 py-2.5 text-sm font-medium {{ $filters['warranty'] === 'expired' ? 'bg-brand-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">Expired</a>
                </div>
                @if ($filters['q'] || $filters['warranty'])
                    <a href="{{ route('products.index', ['tab' => 'sales']) }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Reset</a>
                @endif
            </form>

            <div class="mt-5 overflow-x-auto rounded-xl ring-1 ring-slate-100">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="w-8 px-4 py-3"></th>
                            <th class="px-4 py-3">TXN ID</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Warranty Expires</th>
                            <th class="px-4 py-3">Warranty Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($sales as $sale)
                            <tr class="cursor-pointer hover:bg-slate-50/60" onclick="toggleSaleRow({{ $sale->id }})">
                                <td class="px-4 py-3 text-slate-400">
                                    <svg id="sale-chevron-{{ $sale->id }}" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $sale->code }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $sale->sold_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $sale->items_count }} product{{ $sale->items_count === 1 ? '' : 's' }}</td>
                                <td class="px-4 py-3"><x-badge :tone="$sale->paymentTone()">{{ $sale->payment_method }}</x-badge></td>
                                <td class="px-4 py-3 font-medium text-slate-700">₱{{ number_format($sale->total, 0) }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $sale->warranty_expires_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3"><x-badge :tone="$sale->warrantyTone()">{{ $sale->warrantyLabel() }}</x-badge></td>
                            </tr>
                            <tr id="sale-detail-{{ $sale->id }}" class="hidden bg-slate-50/50">
                                <td colspan="8" class="px-4 py-3">
                                    <table class="w-full table-fixed text-xs">
                                        <thead>
                                            <tr class="text-slate-400">
                                                <th class="w-[46%] py-1 text-left font-medium">Product</th>
                                                <th class="w-[12%] py-1 text-right font-medium">Qty</th>
                                                <th class="w-[21%] py-1 text-right font-medium">Unit Price</th>
                                                <th class="w-[21%] py-1 text-right font-medium">Line Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($sale->items as $item)
                                                <tr>
                                                    <td class="py-1.5 text-slate-600">{{ $item->product_name }}</td>
                                                    <td class="py-1.5 text-right text-slate-600">{{ $item->quantity }}</td>
                                                    <td class="py-1.5 text-right text-slate-600">₱{{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="py-1.5 text-right font-medium text-slate-700">₱{{ number_format($item->line_total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-14 text-center">
                                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    <p class="mt-2 text-sm text-slate-400">No sales records match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="mt-4 border-t border-slate-100 pt-4">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    @else
        @if ($isArchive)
            @php $archivedTotal = \App\Models\Product::whereNotNull('archived_at')->count(); @endphp
            <div class="mb-4 flex items-start justify-between gap-3 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Archived Products</p>
                        <p class="text-xs text-amber-700">
                            {{ $products->total() === 0 ? 'No products in archive.' : $products->total().' product'.($products->total() === 1 ? '' : 's').' in archive.' }}
                            Products moved here can be restored at any time.
                        </p>
                    </div>
                </div>
                @if ($archivedTotal > 0 && auth()->user()->role !== 'technician')
                    <button type="button" onclick="openArchiveDeleteAllModal({{ $archivedTotal }})"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        Delete All
                    </button>
                @endif
            </div>
        @endif

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="{{ $isArchive ? 'Search archived products...' : 'Search by product ID or name...' }}"
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-11 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                </div>
                <button type="submit" class="rounded-lg bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 hover:bg-amber-100">Search</button>
                @if ($filters['q'])
                    <a href="{{ route('products.index', ['tab' => $tab]) }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Reset</a>
                @endif
            </form>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('products.index', ['tab' => $tab, 'q' => $filters['q']]) }}"
                    class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $filters['category'] === '' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</a>
                @foreach (\App\Models\Category::orderBy('name')->pluck('name') as $category)
                    <a href="{{ route('products.index', ['tab' => $tab, 'q' => $filters['q'], 'category' => $category]) }}"
                        class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $filters['category'] === $category ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $category }}</a>
                @endforeach
            </div>

            <div class="mt-5 overflow-x-auto rounded-xl ring-1 ring-slate-100">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">{{ $isArchive ? 'PS-ID' : 'PI-ID' }}</th>
                            <th class="px-4 py-3">Product Name</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">{{ $isArchive ? 'Last Stock' : 'Stock' }}</th>
                            @if ($isArchive)
                                <th class="px-4 py-3">Archived On</th>
                            @else
                                <th class="px-4 py-3">Availability</th>
                            @endif
                            @if ($isArchive)
                                <th class="px-4 py-3">Status</th>
                            @endif
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($products as $product)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $isArchive ? str_replace('PI-', 'PS-', $product->code) : $product->code }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="flex items-center gap-4">
                                        @if ($product->imageUrl())
                                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-20 w-20 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                                        @else
                                            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-300">
                                                <svg class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                            </span>
                                        @endif
                                        <button type="button"
                                            onclick="openProductViewModal({{ Js::from([
                                                'name' => $product->name,
                                                'code' => $isArchive ? str_replace('PI-', 'PS-', $product->code) : $product->code,
                                                'category' => $product->category->name,
                                                'price' => '₱'.number_format($product->price, 2),
                                                'stock' => (string) $product->stock,
                                                'availability_label' => $product->availabilityLabel(),
                                                'availability_tone' => $product->availabilityTone(),
                                                'status_label' => ucfirst($product->status),
                                                'status_tone' => $product->statusTone(),
                                                'image_url' => $product->imageUrl(),
                                            ]) }})"
                                            class="text-left text-lg font-semibold text-slate-800 hover:text-brand-600 hover:underline">
                                            {{ $product->name }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-badge tone="brand" solid>{{ $product->category->name }}</x-badge></td>
                                <td class="px-4 py-3 font-medium text-slate-700">₱{{ number_format($product->price, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if ($isArchive)
                                        <span class="text-slate-600">{{ $product->stock }}</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-14 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, $product->stock * 5) }}%"></div>
                                            </div>
                                            <span class="text-xs text-slate-500">{{ $product->stock }}</span>
                                        </div>
                                    @endif
                                </td>
                                @if ($isArchive)
                                    <td class="px-4 py-3 text-slate-600">{{ $product->archived_at?->format('M j, Y') ?? '—' }}</td>
                                @else
                                    <td class="px-4 py-3"><x-badge :tone="$product->availabilityTone()" solid>{{ $product->availabilityLabel() }}</x-badge></td>
                                @endif
                                @if ($isArchive)
                                    <td class="px-4 py-3"><x-badge :tone="$product->statusTone()">{{ ucfirst($product->status) }}</x-badge></td>
                                @endif
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($isArchive)
                                            @if (auth()->user()->role === 'technician')
                                                <span class="text-xs text-slate-300">View only</span>
                                            @else
                                                <form method="POST" action="{{ route('products.restore', $product) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" title="Restore to catalog" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                                                    </button>
                                                </form>
                                                <button type="button" title="Delete permanently"
                                                    onclick="openProductDeleteModal({{ $product->id }}, {{ Js::from($product->name) }})"
                                                    class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                </button>
                                            @endif
                                        @elseif (auth()->user()->role === 'technician')
                                            <span class="text-xs text-slate-300">View only</span>
                                        @else
                                            <button type="button" title="Edit"
                                                onclick="openProductEditModal({{ Js::from([
                                                    'id' => $product->id,
                                                    'name' => $product->name,
                                                    'category' => $product->category->name,
                                                    'price' => (string) $product->price,
                                                    'stock' => $product->stock,
                                                    'on_order' => (bool) $product->on_order,
                                                    'status' => $product->status,
                                                    'code' => $product->code,
                                                    'image_url' => $product->imageUrl(),
                                                ]) }})"
                                                class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                            </button>
                                            <form method="POST" action="{{ route('products.archive', $product) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Archive" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-amber-600">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                                                </button>
                                            </form>
                                            <button type="button" title="Delete permanently"
                                                onclick="openProductDeleteModal({{ $product->id }}, {{ Js::from($product->name) }})"
                                                class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isArchive ? 8 : 7 }}" class="py-14 text-center">
                                    @if ($isArchive)
                                        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v11.25A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V7.5M3.75 7.5h16.5M3.75 7.5a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3h16.5a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5M10 11.25h4" /></svg>
                                        <p class="mt-2 text-sm text-slate-400">Archive is empty</p>
                                    @else
                                        <p class="text-sm text-slate-400">No products match your filters.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="mt-4 border-t border-slate-100 pt-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Add / Edit Product modal --}}
    <div id="product-form-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('product-form-modal')">
        <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex shrink-0 items-center justify-between px-6 py-3">
                <h2 id="pf-title" class="text-base font-semibold text-white">Add Product</h2>
                <button type="button" onclick="closeModal('product-form-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="product-form" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="min-h-0 flex-1 space-y-3 overflow-y-scroll px-6 py-4">
                @csrf
                <input type="hidden" name="_method" id="pf-method" value="POST">

                <div class="flex items-center gap-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-brand-600 bg-brand-900/60">
                        <img id="pf-image-preview" src="" alt="" class="hidden h-full w-full object-cover">
                        <div id="pf-image-placeholder" class="flex h-full w-full items-center justify-center text-brand-300">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <label for="pf-image" class="block text-sm font-medium text-brand-100">Product Image</label>
                        <input type="file" id="pf-image" name="image" accept="image/png,image/jpeg,image/webp" onchange="previewProductImage(this)"
                            class="mt-1.5 block w-full text-sm text-brand-100 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-500 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-400">
                        <p class="mt-1 text-xs text-brand-300">JPG, PNG or WebP · up to 2&nbsp;MB</p>
                        @error('image') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        <label id="pf-remove-image-wrap" class="mt-1.5 hidden items-center gap-1.5 text-xs text-brand-100">
                            <input type="checkbox" id="pf-remove-image" name="remove_image" value="1" class="rounded border-brand-600">
                            Remove current image
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-brand-100">Product ID</label>
                        <input type="text" id="pf-code-display" readonly
                            class="mt-1.5 block w-full cursor-not-allowed rounded-lg border border-brand-600 bg-brand-900/60 px-3.5 py-2 text-sm text-brand-100">
                    </div>
                    <div>
                        <label for="pf-category" class="block text-sm font-medium text-brand-100">Category *</label>
                        <select id="pf-category" name="category" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                            @foreach (\App\Models\Category::orderBy('name')->pluck('name') as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="pf-name" class="block text-sm font-medium text-brand-100">Product Name *</label>
                        <input type="text" id="pf-name" name="name" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('name') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pf-price" class="block text-sm font-medium text-brand-100">Price (PHP) *</label>
                        <input type="number" id="pf-price" name="price" step="0.01" min="0" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('price') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pf-stock" class="block text-sm font-medium text-brand-100">Stock *</label>
                        <input type="number" id="pf-stock" name="stock" min="0" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                        @error('stock') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-brand-100">Availability</label>
                        <div class="mt-1.5 flex items-center justify-between gap-3 rounded-lg border border-brand-600 bg-brand-900/60 px-3.5 py-2">
                            <span id="pf-availability-badge" class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600">In Stock</span>
                            <span class="text-xs text-brand-100/80">Set automatically from stock level</span>
                        </div>
                        <label class="mt-1.5 flex items-center gap-2 text-xs text-brand-100">
                            <input type="checkbox" id="pf-on-order" name="on_order" value="1"
                                class="h-4 w-4 shrink-0 rounded border-brand-600 bg-brand-900/60 text-brand-500 focus:ring-brand-400">
                            Restock ordered &mdash; show as &ldquo;On Order&rdquo; while stock is 0
                        </label>
                    </div>
                    <div>
                        <label for="pf-status" class="block text-sm font-medium text-brand-100">Status *</label>
                        <select id="pf-status" name="status" required
                            class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-brand-600 px-6 py-3">
                <button type="button" onclick="closeModal('product-form-modal')" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
                <button type="submit" form="product-form" id="pf-submit" class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-400">Add Product</button>
            </div>
        </div>
    </div>

    {{-- Delete Product modal --}}
    <div id="product-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('product-delete-modal')">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.625 3.75h.008v.008h-.008v-.008Z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Delete Product</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to permanently delete <strong id="pd-name" class="font-semibold text-slate-700"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <form id="product-delete-form" method="POST" class="mt-5 flex items-center justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('product-delete-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Delete</button>
            </form>
        </div>
    </div>

    {{-- Delete All Archived Products modal --}}
    <div id="archive-delete-all-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('archive-delete-all-modal')">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.625 3.75h.008v.008h-.008v-.008Z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Delete All Archived Products</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to permanently delete all <strong id="pda-count" class="font-semibold text-slate-700"></strong> products in the Archive? This clears the entire archive, regardless of any search or category filter, and cannot be undone.</p>
                </div>
            </div>
            <form id="archive-delete-all-form" method="POST" action="{{ route('products.archive.destroyAll') }}" class="mt-5 flex items-center justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('archive-delete-all-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete All</button>
            </form>
        </div>
    </div>

    {{-- Product Details modal --}}
    <div id="product-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('product-view-modal')">
        <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-brand-950 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Product Details</h2>
                <button type="button" onclick="closeModal('product-view-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="grid gap-4 px-6 pb-6 sm:grid-cols-2">
                {{-- Left: picture (top) + product name (bottom) --}}
                <div class="flex flex-col rounded-2xl bg-white p-5">
                    <img id="pv-image" src="" alt="" class="hidden h-44 w-full rounded-xl bg-white object-contain ring-1 ring-slate-200">
                    <div id="pv-image-placeholder" class="flex h-44 w-full items-center justify-center rounded-xl bg-slate-100 text-slate-300">
                        <svg class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-wide text-brand-600">Product Name</p>
                    <h3 id="pv-name" class="mt-1 text-2xl font-bold text-slate-900"></h3>
                    <p id="pv-code" class="mt-1 text-sm font-semibold text-brand-600"></p>
                </div>
                {{-- Right: details --}}
                <div class="rounded-2xl bg-white p-5">
                    <dl>
                        <div class="flex items-center justify-between border-b border-slate-100 py-3">
                            <dt class="text-sm font-semibold text-brand-600">Category</dt>
                            <dd id="pv-category" class="text-sm font-semibold text-slate-800"></dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 py-3">
                            <dt class="text-sm font-semibold text-brand-600">Price</dt>
                            <dd id="pv-price" class="text-sm font-semibold text-slate-800"></dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 py-3">
                            <dt class="text-sm font-semibold text-brand-600">Stock</dt>
                            <dd id="pv-stock" class="text-sm font-semibold text-slate-800"></dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 py-3">
                            <dt class="text-sm font-semibold text-brand-600">Availability</dt>
                            <dd id="pv-availability" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"></dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-sm font-semibold text-brand-600">Status</dt>
                            <dd id="pv-status" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <script>
        const PRODUCT_UPDATE_URL_TEMPLATE = "{{ route('products.update', ['product' => '__ID__']) }}";
        const PRODUCT_DESTROY_URL_TEMPLATE = "{{ route('products.destroy', ['product' => '__ID__']) }}";
        const PRODUCT_STORE_URL = "{{ route('products.store') }}";
        const PRODUCT_NEXT_CODE = @json($nextCode);

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

        function setProductImagePreview(url) {
            const img = document.getElementById('pf-image-preview');
            const placeholder = document.getElementById('pf-image-placeholder');
            if (url) {
                img.src = url;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                img.src = '';
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
            }
        }

        function previewProductImage(input) {
            const file = input.files && input.files[0];
            setProductImagePreview(file ? URL.createObjectURL(file) : null);
            if (file) {
                const remove = document.getElementById('pf-remove-image');
                if (remove) remove.checked = false;
            }
        }

        function openProductCreateModal() {
            document.getElementById('product-form').reset();
            document.getElementById('product-form').action = PRODUCT_STORE_URL;
            document.getElementById('pf-method').value = 'POST';
            document.getElementById('pf-title').textContent = 'Add Product';
            document.getElementById('pf-submit').textContent = 'Add Product';
            document.getElementById('pf-code-display').value = PRODUCT_NEXT_CODE;
            document.getElementById('pf-remove-image-wrap').classList.add('hidden');
            setProductImagePreview(null);
            refreshAvailabilityBadge();
            openModal('product-form-modal');
        }

        const AVAILABILITY_TONES = {
            in_stock: { label: 'In Stock', tone: 'teal' },
            low_stock: { label: 'Low Stock', tone: 'amber' },
            out_of_stock: { label: 'Out of Stock', tone: 'rose' },
            on_order: { label: 'On Order', tone: 'indigo' },
        };
        const LOW_STOCK_THRESHOLD = {{ \App\Models\Product::LOW_STOCK_THRESHOLD }};
        // Solid variants for Availability only — mirrors the `solid` prop on the badge
        // component used in the Catalog table; Status (active/inactive, an Archive-tab
        // concept) keeps the pastel PV_TONES below.
        const PV_SOLID_TONES = {
            amber: 'bg-yellow-500 text-white', brand: 'bg-blue-500 text-white', teal: 'bg-green-500 text-white',
            rose: 'bg-red-500 text-white', indigo: 'bg-indigo-500 text-white', slate: 'bg-slate-500 text-white',
        };

        function refreshAvailabilityBadge() {
            const stockRaw = document.getElementById('pf-stock').value;
            const stock = parseInt(stockRaw, 10);
            const onOrder = document.getElementById('pf-on-order').checked;
            let key;
            if (!Number.isFinite(stock) || stock <= 0) {
                key = onOrder ? 'on_order' : 'out_of_stock';
            } else if (stock <= LOW_STOCK_THRESHOLD) {
                key = 'low_stock';
            } else {
                key = 'in_stock';
            }
            const info = AVAILABILITY_TONES[key];
            const badge = document.getElementById('pf-availability-badge');
            badge.textContent = info.label;
            badge.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ' + (PV_SOLID_TONES[info.tone] || PV_SOLID_TONES.slate);
        }

        document.getElementById('pf-stock').addEventListener('input', refreshAvailabilityBadge);
        document.getElementById('pf-on-order').addEventListener('change', refreshAvailabilityBadge);

        function openProductEditModal(data) {
            document.getElementById('product-form').reset();
            document.getElementById('product-form').action = PRODUCT_UPDATE_URL_TEMPLATE.replace('__ID__', data.id);
            document.getElementById('pf-method').value = 'PUT';
            document.getElementById('pf-title').textContent = 'Edit Product';
            document.getElementById('pf-submit').textContent = 'Save Changes';
            document.getElementById('pf-code-display').value = data.code;
            document.getElementById('pf-name').value = data.name || '';
            document.getElementById('pf-category').value = data.category || '';
            document.getElementById('pf-price').value = data.price || '';
            document.getElementById('pf-stock').value = data.stock ?? '';
            document.getElementById('pf-on-order').checked = !!data.on_order;
            document.getElementById('pf-status').value = data.status || 'active';
            refreshAvailabilityBadge();
            setProductImagePreview(data.image_url || null);
            document.getElementById('pf-remove-image-wrap').classList.toggle('hidden', !data.image_url);
            openModal('product-form-modal');
        }

        function openProductDeleteModal(id, name) {
            document.getElementById('product-delete-form').action = PRODUCT_DESTROY_URL_TEMPLATE.replace('__ID__', id);
            document.getElementById('pd-name').textContent = name;
            openModal('product-delete-modal');
        }

        function openArchiveDeleteAllModal(count) {
            document.getElementById('pda-count').textContent = count;
            openModal('archive-delete-all-modal');
        }

        const PV_TONES = {
            amber: 'bg-amber-100 text-amber-700',
            brand: 'bg-brand-100 text-brand-700',
            teal: 'bg-teal-100 text-teal-700',
            rose: 'bg-rose-100 text-rose-700',
            indigo: 'bg-indigo-100 text-indigo-700',
            violet: 'bg-violet-100 text-violet-700',
            slate: 'bg-slate-100 text-slate-600',
        };

        function openProductViewModal(data) {
            const img = document.getElementById('pv-image');
            const placeholder = document.getElementById('pv-image-placeholder');
            if (data.image_url) {
                img.src = data.image_url;
                img.alt = data.name;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                img.removeAttribute('src');
                placeholder.classList.remove('hidden');
            }

            document.getElementById('pv-name').textContent = data.name;
            document.getElementById('pv-code').textContent = data.code;
            document.getElementById('pv-category').textContent = data.category;
            document.getElementById('pv-price').textContent = data.price;
            document.getElementById('pv-stock').textContent = data.stock;

            const avail = document.getElementById('pv-availability');
            avail.textContent = data.availability_label;
            avail.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ' + (PV_SOLID_TONES[data.availability_tone] || PV_SOLID_TONES.slate);

            const status = document.getElementById('pv-status');
            status.textContent = data.status_label;
            status.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ' + (PV_TONES[data.status_tone] || PV_TONES.slate);

            openModal('product-view-modal');
        }

        @if ($errors->any() && old('name') !== null)
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('pf-code-display').value = PRODUCT_NEXT_CODE;
                document.getElementById('pf-name').value = @json(old('name'));
                document.getElementById('pf-category').value = @json(old('category'));
                document.getElementById('pf-price').value = @json(old('price'));
                document.getElementById('pf-stock').value = @json(old('stock'));
                document.getElementById('pf-on-order').checked = @json((bool) old('on_order'));
                document.getElementById('pf-status').value = @json(old('status'));
                setProductImagePreview(null);
                refreshAvailabilityBadge();
                openModal('product-form-modal');
            });
        @endif

        function toggleSaleRow(id) {
            const row = document.getElementById('sale-detail-' + id);
            const chevron = document.getElementById('sale-chevron-' + id);
            row.classList.toggle('hidden');
            chevron.classList.toggle('rotate-90');
        }

    </script>
</x-app-layout>
