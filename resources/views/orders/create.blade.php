@extends('layouts.app')

@section('title', 'New Order')

@section('content')
<form method="POST" action="{{ route('orders.store') }}" id="order-form" class="space-y-4">
    @csrf

    {{-- ── Order Details ───────────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Order Details</h2>
        <div class="grid sm:grid-cols-4 gap-4">
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Date</label>
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 appearance-none">
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Customer Handle</label>
                <input type="text" name="customer_handle" placeholder="@username" required
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Phone</label>
                <input type="text" name="customer_phone" placeholder="0xx xxx xxx"
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Source</label>
                <select name="source" class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    @foreach (['TikTok', 'IG', 'FB', 'Website', 'Other'] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-4 min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Delivery Location</label>
                <input type="text" name="customer_location" placeholder="Address / landmark"
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
        </div>
    </div>

    {{-- ── What They Ordered ───────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">What They Ordered</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Collection</label>
                <select id="collection-select" required class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    <option value="">Select collection...</option>
                    @foreach ($collections as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Design</label>
                <select name="design_id" id="design-select" required disabled
                        class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 disabled:bg-zinc-50 disabled:text-zinc-400 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    <option value="">Select collection first...</option>
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Shirt Type</label>
                <select name="shirt_type_id" id="shirt-type-select" class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    <option value="">Select type...</option>
                    @foreach ($shirtTypes as $t)
                        <option value="{{ $t->id }}" data-name="{{ $t->name }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Size</label>
                <select name="size" id="size-select" required class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    @foreach (['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'] as $s)
                        <option value="{{ $s }}" {{ $s === 'M' ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Color</label>
                <select name="color" id="color-input" required class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    <option value="">Select color...</option>
                    @foreach (($shirtColors ?? $colors ?? []) as $color)
                        <option value="{{ $color->name }}" {{ old('color') == $color->name ? 'selected' : '' }}>
                            {{ $color->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Stock status panels ─────────────────────────────── --}}
        <div class="grid sm:grid-cols-2 gap-3">
            <div id="shirt-status-panel" class="rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0">
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">👕 Shirt Stock Status</p>
                <p class="text-xs text-zinc-500" id="shirt-status-text">Select shirt type, size, and color...</p>
            </div>
            <div id="film-status-panel" class="rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0">
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">🎞️ Film Stock Status</p>
                <p class="text-xs text-zinc-500" id="film-status-text">Select a design first...</p>
            </div>
        </div>

        {{-- ── Full-Width Dropdown Action ── --}}
        <div id="stock-use-gate" class="hidden mt-4 min-w-0">
            <label for="stock-use-select" id="stock-use-label" class="block text-xs font-semibold text-zinc-700 mb-1.5">
                Shirt Stock Action
            </label>
            <select id="stock-use-select" 
                    class="w-full max-w-full min-w-0 rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-xs font-medium text-zinc-900 shadow-xs focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                {{-- Options dynamically injected via JS --}}
            </select>
        </div>

        {{-- Hidden fields passed to backend --}}
        <input type="hidden" name="shirt_status" id="shirt-status-field" value="Not Yet">
        <input type="hidden" name="film_status" id="film-status-field" value="No Film">
        <input type="hidden" name="printed_shirt_id" id="printed-shirt-id-field" value="">
    </div>

    {{-- ── Pricing ──────────────────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Pricing</h2>
        <div class="grid sm:grid-cols-4 gap-4 mb-4">
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Base Price ($)</label>
                <input type="number" step="0.01" name="base_price" id="base-price" value="12.00" required
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Delivery Fee ($)</label>
                <input type="number" step="0.01" name="delivery_fee" id="delivery-fee" value="2.00"
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Payment Status</label>
                <select name="payment_status" id="payment-status" class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                    <option value="Not Yet">Not Yet</option>
                    <option value="Partial">Partial</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Payment Method</label>
                <input type="text" name="payment_method" placeholder="ABA, Cash..."
                       class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
        </div>

        {{-- Only shown when Payment Status = Partial --}}
        <div id="partial-amount-wrap" class="hidden mb-4 sm:w-1/4 min-w-0">
            <label class="block text-xs font-medium text-zinc-700 mb-1.5">Amount Paid So Far ($)</label>
            <input type="number" step="0.01" name="partial_amount" id="partial-amount" value="0.00"
                   class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
        </div>

        <div class="flex items-center justify-between rounded-lg bg-zinc-50 border border-zinc-200 px-4 py-3">
            <span class="text-xs font-medium text-zinc-600">Total</span>
            <span class="text-base font-bold text-zinc-900" id="total-preview">$0.00</span>
        </div>

        <div class="mt-4 min-w-0">
            <label class="block text-xs font-medium text-zinc-700 mb-1.5">Notes</label>
            <textarea name="notes" rows="3" placeholder="Any special notes..."
                      class="w-full max-w-full min-w-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900"></textarea>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="h-9 px-5 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">Save Order</button>
        <a href="{{ route('orders.index') }}" class="h-9 px-5 flex items-center rounded-lg bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // ── Cascading Collection -> Design Dropdown ──────────────────
    @php
        $designsByCollection = [];
        foreach ($collections as $c) {
            $designsByCollection[$c->id] = [];
            foreach ($c->designs as $d) {
                $designsByCollection[$c->id][] = [
                    'id' => $d->id,
                    'name' => $d->name,
                    'has_front' => $d->has_front,
                    'has_back' => $d->has_back,
                ];
            }
        }
    @endphp
    const collectionsData = @json($designsByCollection);

    const collectionSelect = document.getElementById('collection-select');
    const designSelect = document.getElementById('design-select');

    if (collectionSelect) {
        collectionSelect.addEventListener('change', () => {
            const designs = collectionsData[collectionSelect.value] || [];
            designSelect.innerHTML = designs.length
                ? designs.map(d => `<option value="${d.id}">${d.name}</option>`).join('')
                : '<option value="">No designs in this collection</option>';
            designSelect.disabled = designs.length === 0;
            checkFilm();
        });
    }

    // ── Shirt Stock Check ───────────────────────────────────────
    let currentShirtCheck = null;

    async function checkShirt() {
        const shirtTypeEl = document.getElementById('shirt-type-select');
        const shirtTypeId = shirtTypeEl ? shirtTypeEl.value : '';
        const shirtTypeName = shirtTypeEl && shirtTypeEl.options[shirtTypeEl.selectedIndex] 
            ? shirtTypeEl.options[shirtTypeEl.selectedIndex].getAttribute('data-name') || shirtTypeEl.options[shirtTypeEl.selectedIndex].text 
            : '';
        const size = document.getElementById('size-select').value;
        const colorEl = document.getElementById('color-input');
        const color = colorEl ? colorEl.value.trim() : '';
        const designId = designSelect ? designSelect.value : '';
        const panelText = document.getElementById('shirt-status-text');
        const panel = document.getElementById('shirt-status-panel');

        if (!size || !color) {
            panel.className = 'rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0';
            panelText.className = 'text-xs text-zinc-500';
            panelText.textContent = 'Select shirt type, size, and color...';
            currentShirtCheck = null;
            updateStockGate();
            return;
        }

        panelText.textContent = 'Checking...';
        const params = new URLSearchParams({ size, color });
        if (shirtTypeId) {
            params.set('shirt_type_id', shirtTypeId);
            params.set('type', shirtTypeName || shirtTypeId);
        }
        if (designId) params.set('design_id', designId);

        try {
            const res = await fetch(`{{ route('orders.api.check-inventory') }}?${params.toString()}`);
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const data = await res.json();
            currentShirtCheck = data.shirt || null;

            renderShirtStatus(data.shirt, panel, panelText);
            updateStockGate();
        } catch (e) {
            console.error('Shirt stock check failed:', e);
            panel.className = 'rounded-lg bg-rose-50 border border-rose-200 p-3.5 min-w-0';
            panelText.className = 'text-xs font-medium text-rose-800';
            panelText.textContent = 'Unable to verify stock status.';
            currentShirtCheck = null;
            updateStockGate();
        }
    }

    function renderShirtStatus(shirt, panel, panelText) {
        if (!shirt) {
            panel.className = 'rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0';
            panelText.className = 'text-xs text-zinc-500';
            panelText.textContent = 'No matching shirt stock found.';
            return;
        }
        const colorMap = { 
            success: ['bg-emerald-50', 'border-emerald-200', 'text-emerald-800'], 
            error: ['bg-rose-50', 'border-rose-200', 'text-rose-800'], 
            warning: ['bg-zinc-50', 'border-zinc-200', 'text-zinc-500'] 
        };
        panel.className = `rounded-lg border p-3.5 min-w-0 ${(colorMap[shirt.class] || colorMap.warning).join(' ')}`;
        panelText.className = 'text-xs font-medium';
        panelText.textContent = shirt.message || 'Stock checked.';
    }

    // ── Film Stock Check ─────────────────────────────────────────
    async function checkFilm() {
        const designId = designSelect ? designSelect.value : '';
        const colorEl = document.getElementById('color-input');
        const color = colorEl ? colorEl.value.trim() : '';
        const panelText = document.getElementById('film-status-text');
        const panel = document.getElementById('film-status-panel');

        if (!designId) {
            panel.className = 'rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0';
            panelText.className = 'text-xs text-zinc-500';
            panelText.textContent = 'Select a design first...';
            return;
        }

        panelText.textContent = 'Checking...';
        const params = new URLSearchParams({ design_id: designId });
        if (color) params.set('color', color);

        try {
            const res = await fetch(`{{ route('orders.api.check-inventory') }}?${params.toString()}`);
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const data = await res.json();

            if (data.film) {
                const colorMap = { 
                    success: ['bg-emerald-50', 'border-emerald-200', 'text-emerald-800'], 
                    error: ['bg-rose-50', 'border-rose-200', 'text-rose-800'] 
                };
                panel.className = `rounded-lg border p-3.5 min-w-0 ${(colorMap[data.film.class] || colorMap.error).join(' ')}`;
                panelText.className = 'text-xs font-medium';
                panelText.textContent = data.film.message || 'Film stock checked.';
                document.getElementById('film-status-field').value = data.film.status || 'No Film';
            } else {
                panel.className = 'rounded-lg bg-zinc-50 border border-zinc-200 p-3.5 min-w-0';
                panelText.className = 'text-xs text-zinc-500';
                panelText.textContent = 'No film stock info available.';
            }
        } catch (e) {
            console.error('Film stock check failed:', e);
            panel.className = 'rounded-lg bg-rose-50 border border-rose-200 p-3.5 min-w-0';
            panelText.className = 'text-xs font-medium text-rose-800';
            panelText.textContent = 'Unable to check film status.';
        }
    }

    // ── Stock Action Dropdown Logic ──────────────────────────────
    const stockGate = document.getElementById('stock-use-gate');
    const stockSelect = document.getElementById('stock-use-select');
    const stockLabel = document.getElementById('stock-use-label');

    function updateStockGate() {
        if (!currentShirtCheck || (currentShirtCheck.quantity <= 0 && !currentShirtCheck.printed_available)) {
            stockGate.classList.add('hidden');
            resetStockFields();
            return;
        }

        stockGate.classList.remove('hidden');

        if (currentShirtCheck.printed_available && currentShirtCheck.printed_shirt_id) {
            stockLabel.textContent = '⚡ Pre-printed stock found — Choose stock action:';
            stockSelect.innerHTML = `
                <option value="use_printed">Use available stock (${currentShirtCheck.quantity} in stock)</option>
                <option value="dont_use">Don't use — print new shirt</option>
            `;
        } else {
            stockLabel.textContent = '📦 Blank shirt stock found — Choose stock action:';
            stockSelect.innerHTML = `
                <option value="use_blank">Use available stock (${currentShirtCheck.quantity} in stock)</option>
                <option value="dont_use">Don't use — buy new</option>
            `;
        }

        handleStockSelectChange();
    }

    function handleStockSelectChange() {
        const val = stockSelect.value;
        const shirtStatusField = document.getElementById('shirt-status-field');
        const printedField = document.getElementById('printed-shirt-id-field');

        if (val === 'use_printed') {
            if (currentShirtCheck && currentShirtCheck.printed_shirt_id) {
                printedField.value = currentShirtCheck.printed_shirt_id;
                shirtStatusField.value = 'Done';
            } else {
                printedField.value = '';
                shirtStatusField.value = 'Not Yet';
            }
        } else if (val === 'use_blank') {
            printedField.value = '';
            shirtStatusField.value = 'Bought';
        } else {
            resetStockFields();
        }
    }

    function resetStockFields() {
        document.getElementById('printed-shirt-id-field').value = '';
        document.getElementById('shirt-status-field').value = 'Not Yet';
    }

    if (stockSelect) {
        stockSelect.addEventListener('change', handleStockSelectChange);
    }

    document.getElementById('order-form').addEventListener('submit', () => {
        if (stockSelect.value !== 'use_printed') {
            document.getElementById('printed-shirt-id-field').value = '';
        }
    });

    // ── Event Listeners ─────────────────────────────────────────
    ['shirt-type-select', 'size-select'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', checkShirt);
    });
    
    const colorInput = document.getElementById('color-input');
    if (colorInput) {
        ['change', 'input'].forEach(evt => 
            colorInput.addEventListener(evt, () => { checkShirt(); checkFilm(); })
        );
    }

    if (designSelect) {
        designSelect.addEventListener('change', () => { checkFilm(); checkShirt(); });
    }

    // ── Payment & Price Preview ─────────────────────────────────
    const paymentStatus = document.getElementById('payment-status');
    const partialWrap = document.getElementById('partial-amount-wrap');
    if (paymentStatus && partialWrap) {
        paymentStatus.addEventListener('change', () => {
            partialWrap.classList.toggle('hidden', paymentStatus.value !== 'Partial');
        });
    }

    function updateTotal() {
        const base = parseFloat(document.getElementById('base-price').value) || 0;
        const delivery = parseFloat(document.getElementById('delivery-fee').value) || 0;
        document.getElementById('total-preview').textContent = '$' + (base + delivery).toFixed(2);
    }
    
    const basePrice = document.getElementById('base-price');
    const deliveryFee = document.getElementById('delivery-fee');
    if (basePrice) basePrice.addEventListener('input', updateTotal);
    if (deliveryFee) deliveryFee.addEventListener('input', updateTotal);

    // ── Run on page load ──
    updateTotal();
</script>
@endpush