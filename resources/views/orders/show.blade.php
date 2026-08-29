@extends('layouts.app')

@section('title', 'Order #' . $order->order_number)

@section('header-actions')
    <a href="{{ route('orders.edit', $order) }}"
       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">
        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Edit Order
    </a>
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">
        Back
    </a>
@endsection

@section('content')
<div class="w-full space-y-5" x-data="{ openDeleteModal: false }">

    {{-- ── 1. Top Readiness Banner (Full Width) ─────────────────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-11 h-11 rounded-xl bg-zinc-100 border border-zinc-200/60 flex items-center justify-center text-zinc-900 font-bold text-sm">
                #{{ substr($order->order_number, -3) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-base font-bold text-zinc-900">Order {{ $order->order_number }}</h1>
                    @php $readiness = $order->readiness_badge; @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $readiness['class'] }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5"></span>
                        {{ $readiness['label'] }}
                    </span>
                </div>
                <p class="text-xs text-zinc-500 mt-0.5">Placed on {{ $order->order_date ? $order->order_date->format('d M Y') : 'N/A' }} via <span class="font-medium text-zinc-700">{{ $order->source }}</span></p>
            </div>
        </div>

        {{-- Financial Quick Stats --}}
        <div class="flex items-center space-x-6 border-t sm:border-t-0 sm:border-l border-zinc-100 pt-3 sm:pt-0 sm:pl-6 text-xs">
            <div>
                <span class="text-zinc-400 block uppercase tracking-wider text-[10px] font-semibold">Total Revenue</span>
                <span class="text-sm font-bold text-zinc-900">${{ number_format($order->total_price, 2) }}</span>
            </div>
            <div>
                <span class="text-zinc-400 block uppercase tracking-wider text-[10px] font-semibold">Net Profit</span>
                <span class="text-sm font-bold text-emerald-600">${{ number_format($order->profit, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ── 2. 2-Column Main Details Grid (Full Width) ───────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- ── Customer Card ─────────────────────────────────────────────── --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-zinc-100">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Customer Information</h2>
                <span class="px-2 py-0.5 bg-zinc-100 text-zinc-600 rounded text-[11px] font-medium">{{ $order->source }}</span>
            </div>
            <dl class="space-y-3 text-xs">
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Handle / Name</dt><dd class="font-semibold text-zinc-900">{{ $order->customer_handle }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Phone</dt><dd class="text-zinc-700 font-mono">{{ $order->customer_phone ?: '—' }}</dd></div>
                <div class="flex justify-between items-start"><dt class="text-zinc-400">Location</dt><dd class="text-zinc-700 text-right max-w-[65%]">{{ $order->customer_location ?: '—' }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Order Date</dt><dd class="text-zinc-700">{{ $order->order_date ? $order->order_date->format('d M Y') : '—' }}</dd></div>
            </dl>
        </div>

        {{-- ── Item & Garment Details ────────────────────────────────────── --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-zinc-100">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Item & Stock Specs</h2>
                @if ($order->printedShirt)
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded text-[10px] font-semibold">Printed Spare Used</span>
                @else
                    <span class="px-2 py-0.5 bg-zinc-100 text-zinc-600 rounded text-[10px] font-medium">Standard Print</span>
                @endif
            </div>
            <dl class="space-y-3 text-xs">
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Collection</dt><dd class="text-zinc-700">{{ $order->design?->collection?->name ?? '—' }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Design Name</dt><dd class="font-semibold text-zinc-900">{{ $order->design?->name ?? '—' }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Garment Size</dt><dd class="px-2 py-0.5 bg-zinc-100 text-zinc-800 rounded font-semibold text-[11px]">{{ $order->size }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Garment Color</dt><dd class="text-zinc-700 font-medium">{{ $order->color }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-zinc-400">Shirt Type</dt><dd class="text-zinc-700">{{ $order->shirtType?->name ?? $order->shirt_type_id ?? 'Standard' }}</dd></div>
            </dl>
        </div>

        {{-- ── Production & Pipeline Status ──────────────────────────────── --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-zinc-100">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Production Status</h2>
                <span class="text-[11px] text-zinc-400">Pipeline Tracking</span>
            </div>
            <dl class="space-y-3 text-xs">
                <div class="flex justify-between items-center">
                    <dt class="text-zinc-400">Shirt Stock</dt>
                    <dd>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $order->shirt_status === 'Done' || $order->shirt_status === 'Bought' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60' }}">
                            {{ $order->shirt_status }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-zinc-400">Film Print</dt>
                    <dd>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold {{ in_array($order->film_status, ['Have Film','Printed','Done','In Stock']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60' }}">
                            {{ $order->film_status }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-zinc-400">Print Press</dt>
                    <dd>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold {{ in_array($order->print_status, ['Printed','Done']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-zinc-100 text-zinc-600' }}">
                            {{ $order->print_status }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-zinc-400">Delivery Status</dt>
                    <dd><span class="badge {{ $order->getDeliveryBadgeClass() }}">{{ $order->delivery_status }}</span></dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-zinc-400">Payment Status</dt>
                    <dd><span class="badge {{ $order->getPaymentBadgeClass() }}">{{ $order->payment_status }}</span></dd>
                </div>
                @if ($order->payment_method)
                    <div class="flex justify-between items-center"><dt class="text-zinc-400">Payment Method</dt><dd class="text-zinc-700 font-medium">{{ $order->payment_method }}</dd></div>
                @endif
                @if ($order->payment_status === 'Partial')
                    <div class="flex justify-between items-center pt-2 border-t border-dashed border-zinc-200">
                        <dt class="text-zinc-400">Partial Amount Paid</dt>
                        <dd class="text-amber-700 font-semibold">${{ number_format($order->partial_amount, 2) }} / ${{ number_format($order->total_price, 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- ── Financial Breakdown ────────────────────────────────────────── --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-zinc-100">
                    <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Financial Breakdown</h2>
                    <span class="text-[11px] text-zinc-400">COGS & Margin</span>
                </div>
                <dl class="space-y-2.5 text-xs">
                    <div class="flex justify-between"><dt class="text-zinc-400">Base Shirt Price</dt><dd class="text-zinc-700 font-mono">${{ number_format($order->base_price, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-400">Delivery Fee Charged</dt><dd class="text-zinc-700 font-mono">${{ number_format($order->delivery_fee, 2) }}</dd></div>
                    <div class="flex justify-between pt-2 border-t border-zinc-100 font-semibold"><dt class="text-zinc-900">Gross Total Revenue</dt><dd class="text-zinc-900 font-mono">${{ number_format($order->total_price, 2) }}</dd></div>
                    
                    <div class="pt-2"></div>
                    <div class="flex justify-between text-zinc-500"><dt>Blank Shirt Cost (COGS)</dt><dd class="text-rose-600 font-mono">-${{ number_format($order->shirt_cost, 2) }}</dd></div>
                    <div class="flex justify-between text-zinc-500"><dt>Film Transfer Cost (COGS)</dt><dd class="text-rose-600 font-mono">-${{ number_format($order->film_cost, 2) }}</dd></div>
                </dl>
            </div>

            <div class="mt-4 pt-3 border-t border-zinc-100 flex items-center justify-between bg-emerald-50/60 p-3 rounded-lg border border-emerald-100">
                <span class="text-xs font-bold text-emerald-900">Estimated Net Profit</span>
                <span class="text-base font-extrabold text-emerald-700 font-mono">${{ number_format($order->profit, 2) }}</span>
            </div>
        </div>

    </div>

    {{-- ── 3. Notes (Full Width Bottom Section) ──────────────────────────────── --}}
    @if ($order->notes)
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-2">Order Notes</h2>
            <div class="p-3 bg-zinc-50/80 rounded-lg border border-zinc-200/50 text-xs text-zinc-700 whitespace-pre-line leading-relaxed">
                {{ $order->notes }}
            </div>
        </div>
    @endif

    {{-- ── 4. Danger Zone (Delete Order) ─────────────────────── --}}
    <div class="bg-red-50/50 border border-red-200/70 rounded-xl p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xs font-bold text-red-900 uppercase tracking-wider">Danger Zone</h2>
            <p class="text-xs text-red-600/80 mt-0.5">Permanently delete Order #{{ $order->order_number }}. Reserved stock will be returned to inventory.</p>
        </div>
        <button type="button" @click="openDeleteModal = true"
                class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-2xs transition-colors shrink-0">
            Delete Order
        </button>
    </div>

    {{-- ── Custom Alpine Delete Modal ────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="openDeleteModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/50 backdrop-blur-xs"
             x-transition:enter="ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div @click.outside="openDeleteModal = false"
                 @keydown.escape.window="openDeleteModal = false"
                 class="w-full max-w-sm bg-white rounded-xl border border-zinc-200/80 p-5 shadow-lg space-y-4"
                 x-transition:enter="ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <div>
                    <h3 class="text-sm font-bold text-zinc-900">Delete Order #{{ $order->order_number }}</h3>
                    <p class="text-xs text-zinc-500 mt-1.5 leading-relaxed">
                        Are you sure you want to delete this order? Reserved stock will be returned to inventory, and this action cannot be undone.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" 
                            @click="openDeleteModal = false"
                            class="h-8 px-3 rounded-lg border border-zinc-200 text-zinc-700 hover:bg-zinc-50 text-xs font-medium transition-colors">
                        Cancel
                    </button>

                    <form action="{{ route('orders.destroy', $order) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="h-8 px-3 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-2xs transition-colors">
                            Delete Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection