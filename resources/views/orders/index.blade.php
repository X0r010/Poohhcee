@extends('layouts.app')

@section('title', 'All Orders')

@section('header-actions')
    <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">+ New Order</a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- ── Jump buttons ─────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <a href="#all-orders" class="inline-flex items-center h-8 px-3 rounded-md bg-zinc-900 text-white text-xs font-medium">All Orders</a>
        @foreach ($collections as $collection)
            <a href="#collection-{{ $collection->id }}"
               class="inline-flex items-center h-8 px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 text-xs font-medium shadow-2xs transition-all">
                {{ $collection->name }}
            </a>
        @endforeach
    </div>

    {{-- ── Global "All Orders" table ───────────────────────────── --}}
    <div id="all-orders" class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs scroll-mt-20">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">All Orders</h2>

        <div class="grid sm:grid-cols-3 md:grid-cols-6 gap-2 mb-4" data-filter-form data-scope="all">
            <input type="text" placeholder="Search customer, order #, notes..." data-filter="search"
                   class="sm:col-span-2 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            
            <select data-filter="collection" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                <option value="">All Collections</option>
                @foreach ($collections as $collection)
                    <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                @endforeach
            </select>

            <select data-filter="readiness" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                <option value="">Any Readiness</option>
                <option value="ready">Ready to Print</option>
                <option value="missing_shirt">Missing Shirt</option>
                <option value="missing_film">Missing Film</option>
                <option value="missing_both">Missing Both</option>
                <option value="printed">Printed</option>
            </select>

            <select data-filter="print_status" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                <option value="">Any Print Status</option>
                @foreach (['Pending', 'Printing', 'Printed', 'Done'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>

            <select data-filter="delivery_status" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                <option value="">Any Delivery Status</option>
                @foreach (['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>

            <select data-filter="payment_status" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                <option value="">Any Payment Status</option>
                @foreach (['Not Yet', 'Partial', 'Paid'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:whitespace-nowrap">
                <thead>
                    <tr class="hidden sm:table-row text-left text-zinc-400 border-b border-zinc-100">
                        <th class="font-medium pb-2">#</th>
                        <th class="font-medium pb-2">Date</th>
                        <th class="font-medium pb-2">Customer</th>
                        <th class="font-medium pb-2">Design</th>
                        <th class="font-medium pb-2">Size</th>
                        <th class="font-medium pb-2">Readiness</th>
                        <th class="font-medium pb-2">Delivery</th>
                        <th class="font-medium pb-2">Payment</th>
                        <th class="font-medium pb-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-all" class="divide-y divide-zinc-50">
                    @include('orders._rows', ['orders' => $globalOrders])
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-center" id="loadmore-wrap-all">
            <button type="button" data-loadmore data-scope="all" data-page="2"
                    class="inline-flex items-center justify-center h-8 px-4 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">
                Load More
            </button>
        </div>
    </div>

    {{-- ── Per-collection sections ─────────────────────────────── --}}
    @foreach ($collections as $collection)
        <div id="collection-{{ $collection->id }}" class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs scroll-mt-20">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">{{ $collection->name }}</h2>

            <div class="grid sm:grid-cols-2 md:grid-cols-5 gap-2 mb-4" data-filter-form data-scope="collection" data-collection-id="{{ $collection->id }}">
                <input type="text" placeholder="Search customer, order #, notes..." data-filter="search"
                       class="sm:col-span-2 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                
                <select data-filter="readiness" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                    <option value="">Any Readiness</option>
                    <option value="ready">Ready to Print</option>
                    <option value="missing_shirt">Missing Shirt</option>
                    <option value="missing_film">Missing Film</option>
                    <option value="missing_both">Missing Both</option>
                    <option value="printed">Printed</option>
                </select>

                <select data-filter="delivery_status" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                    <option value="">Any Delivery Status</option>
                    @foreach (['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled'] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>

                <select data-filter="payment_status" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                    <option value="">Any Payment Status</option>
                    @foreach (['Not Yet', 'Partial', 'Paid'] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs whitespace-nowrap">
                    <thead>
                        <tr class="text-left text-zinc-400 border-b border-zinc-100">
                            <th class="font-medium pb-2">#</th>
                            <th class="font-medium pb-2">Date</th>
                            <th class="font-medium pb-2">Customer</th>
                            <th class="font-medium pb-2">Design</th>
                            <th class="font-medium pb-2">Size</th>
                            <th class="font-medium pb-2">Readiness</th>
                            <th class="font-medium pb-2">Delivery</th>
                            <th class="font-medium pb-2">Payment</th>
                            <th class="font-medium pb-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-{{ $collection->id }}" class="divide-y divide-zinc-50">
                        @include('orders._rows', ['orders' => $collectionOrders[$collection->id]])
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-center" id="loadmore-wrap-{{ $collection->id }}">
                <button type="button" data-loadmore data-scope="collection" data-collection-id="{{ $collection->id }}" data-page="2"
                        class="inline-flex items-center justify-center h-8 px-4 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">
                    Load More
                </button>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Floating Back to Top ────────────────────────────────────── --}}
<button id="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
        class="fixed bottom-6 right-6 hidden items-center justify-center w-10 h-10 rounded-full bg-zinc-900 text-white shadow-lg hover:bg-zinc-800 transition-colors z-40">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
</button>
@endsection

@push('scripts')
<script>
    const backToTop = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        backToTop.classList.toggle('hidden', window.scrollY < 400);
        backToTop.classList.toggle('flex', window.scrollY >= 400);
    });

    function tbodyId(scope, collectionId) {
        return scope === 'collection' ? `tbody-${collectionId}` : 'tbody-all';
    }

    function buildParams(scope, collectionId, page) {
        const params = new URLSearchParams();
        params.set('scope', scope);
        params.set('page', page);
        if (scope === 'collection') params.set('collection_id', collectionId);

        const formSelector = scope === 'collection'
            ? `[data-filter-form][data-scope="collection"][data-collection-id="${collectionId}"]`
            : `[data-filter-form][data-scope="all"]`;
        const form = document.querySelector(formSelector);
        if (form) {
            form.querySelectorAll('[data-filter]').forEach(el => {
                if (el.value) params.set(el.dataset.filter, el.value);
            });
        }
        return params;
    }

    async function fetchRows(scope, collectionId, page) {
        const params = buildParams(scope, collectionId, page);
        const res = await fetch(`{{ route('orders.rows') }}?${params.toString()}`);
        return res.text();
    }

    document.querySelectorAll('[data-loadmore]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const scope = btn.dataset.scope;
            const collectionId = btn.dataset.collectionId;
            const page = parseInt(btn.dataset.page, 10);

            btn.disabled = true;
            btn.textContent = 'Loading...';

            const rowsHtml = await fetchRows(scope, collectionId, page);

            if (rowsHtml.trim() === '') {
                btn.textContent = 'No more orders';
                btn.disabled = true;
                return;
            }

            document.getElementById(tbodyId(scope, collectionId)).insertAdjacentHTML('beforeend', rowsHtml);
            btn.dataset.page = page + 1;
            btn.disabled = false;
            btn.textContent = 'Load More';
        });
    });

    document.querySelectorAll('[data-filter-form]').forEach(form => {
        const scope = form.dataset.scope;
        const collectionId = form.dataset.collectionId;

        form.querySelectorAll('[data-filter]').forEach(el => {
            el.addEventListener('change', async () => {
                const tbody = document.getElementById(tbodyId(scope, collectionId));
                tbody.innerHTML = '<tr><td colspan="9" class="py-6 text-center text-zinc-400">Loading...</td></tr>';

                const rowsHtml = await fetchRows(scope, collectionId, 1);
                tbody.innerHTML = rowsHtml || '<tr><td colspan="9" class="py-6 text-center text-zinc-400">No orders match those filters.</td></tr>';

                const loadMoreBtn = document.querySelector(`#loadmore-wrap-${scope === 'collection' ? collectionId : 'all'} [data-loadmore]`);
                if (loadMoreBtn) {
                    loadMoreBtn.dataset.page = 2;
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Load More';
                }
            });
        });

        const searchInput = form.querySelector('input[data-filter="search"]');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => searchInput.dispatchEvent(new Event('change')), 400);
            });
        }
    });
</script>
@endpush