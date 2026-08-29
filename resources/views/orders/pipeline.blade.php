@extends('layouts.app')

@section('title', 'Pipeline')

@section('header-actions')
    <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">+ New Order</a>
@endsection

@section('content')
<div class="w-full py-2 space-y-6" x-data="{ 
    selectedGroups: [],
    toggleGroup(key) {
        if (this.selectedGroups.includes(key)) {
            this.selectedGroups = this.selectedGroups.filter(g => g !== key);
        } else {
            this.selectedGroups.push(key);
        }
    },
    clearGroups() {
        this.selectedGroups = [];
    }
}">

    {{-- Session Flash Notifications --}}
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3 rounded-lg flex items-center justify-between">
            <span>⚠️ {{ session('error') }}</span>
        </div>
    @endif
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs px-4 py-3 rounded-lg flex items-center justify-between">
            <span>✓ {{ session('success') }}</span>
        </div>
    @endif

    {{-- Money Owed Banner --}}
    <div class="bg-zinc-900 text-white rounded-xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <p class="text-xs text-zinc-400 mb-0.5">Money Owed</p>
            <p class="text-2xl font-bold">${{ number_format($moneyOwed, 2) }}</p>
        </div>
        <p class="text-xs text-zinc-400 sm:max-w-xs sm:text-right">Across {{ $unpaidCount }} unpaid order{{ $unpaidCount === 1 ? '' : 's' }}, wherever they sit in the pipeline</p>
    </div>

    {{-- ── MULTI-SELECT GROUP FILTER BAR (Mobile Swipeable / Desktop Wrapped) ── --}}
    @php
        $totalPipelineCount = collect($groups)->sum(fn($g) => $g->count());
    @endphp
    <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-3.5 shadow-2xs space-y-2">
        <div class="flex items-center justify-between px-1">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Filter Pipeline</span>
            <div class="flex items-center gap-2">
                <span class="text-[11px] sm:text-xs font-semibold text-zinc-500" 
                      x-text="selectedGroups.length === 0 ? 'Showing All' : `Selected (${selectedGroups.length})`"></span>
                <button type="button" 
                        x-show="selectedGroups.length > 0" 
                        @click="clearGroups()" 
                        class="text-[11px] text-rose-600 hover:underline font-medium" 
                        x-cloak>
                    Clear
                </button>
            </div>
        </div>

        {{-- Mobile Swipeable Row / Desktop Grid --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 sm:flex-wrap [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
            <button type="button" 
                    @click="clearGroups()" 
                    :class="selectedGroups.length === 0 ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-zinc-50 text-zinc-600 hover:bg-zinc-100 border-zinc-200'"
                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors inline-flex items-center gap-1.5">
                <span>All Groups</span>
                <span :class="selectedGroups.length === 0 ? 'bg-zinc-700 text-white' : 'bg-zinc-200 text-zinc-600'" class="px-1.5 py-0.2 text-[10px] rounded-full font-bold">
                    {{ $totalPipelineCount }}
                </span>
            </button>

            @foreach ($groupDefs as $key => $def)
                <button type="button" 
                        @click="toggleGroup('{{ $key }}')" 
                        :class="selectedGroups.includes('{{ $key }}') ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-zinc-50 text-zinc-600 hover:bg-zinc-100 border-zinc-200'"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors inline-flex items-center gap-1.5">
                    <span>{{ $def['label'] }}</span>
                    <span :class="selectedGroups.includes('{{ $key }}') ? 'bg-zinc-700 text-white' : 'bg-zinc-200 text-zinc-600'" class="px-1.5 py-0.2 text-[10px] rounded-full font-bold">
                        {{ $groups[$key]->count() }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── GROUPS LOOP ─────────────────────────────────────────────────── --}}
    @foreach ($groupDefs as $key => $def)
        @continue($groups[$key]->isEmpty())
        <div id="{{ \Illuminate\Support\Str::slug($def['label']) }}" 
             x-show="selectedGroups.length === 0 || selectedGroups.includes('{{ $key }}')" 
             x-cloak
             class="space-y-3 scroll-mt-20">
            
            {{-- Group Header --}}
            <div class="flex items-center space-x-2">
                <span class="badge {{ $def['badge'] }} !px-3 !py-1 !rounded-full text-xs font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-60 mr-1 inline-block"></span>
                    {{ $def['label'] }}
                </span>
                <span class="bg-zinc-100 text-zinc-500 rounded-full px-2.5 py-0.5 text-xs font-normal border border-zinc-200">
                    {{ $groups[$key]->count() }} {{ $groups[$key]->count() === 1 ? 'order' : 'orders' }}
                </span>
            </div>

            {{-- Group Orders --}}
            <div class="space-y-4">
                @foreach ($groups[$key] as $order)
                    <div class="bg-white border border-zinc-200 rounded-xl p-4 sm:p-6 shadow-sm space-y-4 hover:border-zinc-300 transition">

                        {{-- Card Header --}}
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-3">
                            <div class="flex items-center space-x-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900">{{ '@' . $order->customer_handle }}</h3>
                                <span class="text-xs text-zinc-400 font-normal">#{{ $order->order_number }}</span>
                                @if (!in_array(strtolower($order->payment_status), ['paid']))
                                    <span class="bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-medium">Unpaid</span>
                                @endif
                            </div>

                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <span class="text-sm font-bold text-zinc-900">${{ number_format($order->total_price, 2) }}</span>
                                <a href="{{ route('orders.edit', $order) }}"
                                   class="text-xs border border-zinc-200 rounded-lg px-2.5 py-1 text-zinc-600 hover:bg-zinc-50 font-medium transition">
                                    Edit
                                </a>
                            </div>
                        </div>

                        {{-- Metadata Sub-row --}}
                        <div class="text-xs text-zinc-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-zinc-800 font-medium">{{ $order->design->name }}</span>
                            <span>·</span>
                            <span>{{ $order->design->collection->name }}</span>
                            <span>·</span>
                            <span>{{ $order->size }} / {{ $order->color }}</span>
                            <span>·</span>
                            <span>{{ $order->order_date ? $order->order_date->format('d M Y') : '' }}</span>
                            <span>·</span>
                            <span>{{ $order->source }}</span>
                            <span>·</span>
                            <span>{{ $order->customer_phone ?: 'no phone' }}</span>
                            <span>·</span>
                            <span class="truncate max-w-[180px] sm:max-w-[220px]" title="{{ $order->customer_location }}">📍 {{ $order->customer_location ?: 'no location' }}</span>
                        </div>

                        {{-- Film Side Badges --}}
                        @if (in_array($order->film_status, ['No Film', 'Ordering']))
                            @php $neededSides = $order->getNeededFilmSides(); @endphp
                            @if (count($neededSides) > 0)
                                <div class="flex items-center space-x-2 pt-1">
                                    @if (count($neededSides) === 2)
                                        <span class="bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1">
                                            <span>⚠️ Front & Back Film Needed</span>
                                        </span>
                                    @elseif (in_array('Front', $neededSides))
                                        <span class="bg-amber-50 text-amber-800 border border-amber-200 px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1">
                                            <span>⚠️ Front Film Needed</span>
                                        </span>
                                    @elseif (in_array('Back', $neededSides))
                                        <span class="bg-indigo-50 text-indigo-800 border border-indigo-200 px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1">
                                            <span>⚠️ Back Film Needed</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endif

                        {{-- ── Action Controls Grid ── --}}
                        <div class="pt-2">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 items-end">

                                {{-- GROUP 1: PRINTING STAGES --}}
                                @if (in_array($key, ['print_pending', 'printing']))
                                    
                                    {{-- Control 1: Print Action Button --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Print Action</label>
                                        <form action="{{ route('orders.advance-print', $order) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full h-9 rounded-lg {{ $key === 'print_pending' ? 'bg-black hover:bg-zinc-800' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white text-xs font-semibold shadow-2xs transition-colors flex items-center justify-center">
                                                {{ $key === 'print_pending' ? '🖨️ Start Print' : '✓ Mark Printed' }}
                                            </button>
                                        </form>
                                    </div>
                                    
                                    {{-- Control 2: Delivery Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Delivery Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="delivery_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Pending" @selected(in_array($order->delivery_status, ['Pending', 'pending']))>Pending</option>
                                                <option value="Packaging" @selected(in_array($order->delivery_status, ['Packaging', 'packaging']))>Packaging</option>
                                                <option value="Delivering" @selected(in_array($order->delivery_status, ['Delivering', 'delivering']))>Delivering</option>
                                                <option value="Delivered" @selected(in_array($order->delivery_status, ['Delivered', 'delivered']))>Delivered</option>
                                                <option value="Cancelled" @selected(in_array($order->delivery_status, ['Cancelled', 'cancelled']))>Cancelled</option>
                                            </select>
                                        </form>
                                    </div>

                                    {{-- Control 3: Payment Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Payment Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="payment_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Not Yet" @selected(in_array($order->payment_status, ['Not Yet', 'not_yet']))>Not Yet</option>
                                                <option value="Partial" @selected(in_array($order->payment_status, ['Partial', 'partial']))>Partial</option>
                                                <option value="Paid" @selected(in_array($order->payment_status, ['Paid', 'paid']))>Paid</option>
                                            </select>
                                        </form>
                                    </div>

                                {{-- GROUP 2: FULFILLMENT / COMPLETED STAGES --}}
                                @elseif (in_array($key, ['printed', 'packaging', 'delivering', 'awaiting_payment']) || ($key === 'unpaid' && in_array(strtolower($order->print_status ?? ''), ['printed', 'done'])))
                                    
                                    {{-- Control 1: Delivery Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Delivery Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="delivery_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Pending" @selected(in_array($order->delivery_status, ['Pending', 'pending']))>Pending</option>
                                                <option value="Packaging" @selected(in_array($order->delivery_status, ['Packaging', 'packaging']))>Packaging</option>
                                                <option value="Delivering" @selected(in_array($order->delivery_status, ['Delivering', 'delivering']))>Delivering</option>
                                                <option value="Delivered" @selected(in_array($order->delivery_status, ['Delivered', 'delivered']))>Delivered</option>
                                                <option value="Cancelled" @selected(in_array($order->delivery_status, ['Cancelled', 'cancelled']))>Cancelled</option>
                                            </select>
                                        </form>
                                    </div>

                                    {{-- Control 2: Payment Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Payment Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="payment_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Not Yet" @selected(in_array($order->payment_status, ['Not Yet', 'not_yet']))>Not Yet</option>
                                                <option value="Partial" @selected(in_array($order->payment_status, ['Partial', 'partial']))>Partial</option>
                                                <option value="Paid" @selected(in_array($order->payment_status, ['Paid', 'paid']))>Paid</option>
                                            </select>
                                        </form>
                                    </div>

                                    {{-- Control 3: Shirt Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Shirt Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="shirt_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Not Yet" @selected(in_array($order->shirt_status, ['Not Yet', 'not_yet']))>Not Yet</option>
                                                <option value="Buying" @selected(in_array($order->shirt_status, ['Buying', 'buying']))>Buying</option>
                                                <option value="Bought" @selected(in_array($order->shirt_status, ['Bought', 'bought']))>Bought</option>
                                                <option value="Done" @selected(in_array($order->shirt_status, ['Done', 'done']))>Done</option>
                                            </select>
                                        </form>
                                    </div>

                                {{-- GROUP 3: DEFAULT (Unpaid [not printed], Buying, Ordering Film) --}}
                                @else
                                    
                                    {{-- Control 1: Shirt Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Shirt Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="shirt_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Not Yet" @selected(in_array($order->shirt_status, ['Not Yet', 'not_yet']))>Not Yet</option>
                                                <option value="Buying" @selected(in_array($order->shirt_status, ['Buying', 'buying']))>Buying</option>
                                                <option value="Bought" @selected(in_array($order->shirt_status, ['Bought', 'bought']))>Bought</option>
                                                <option value="Done" @selected(in_array($order->shirt_status, ['Done', 'done']))>Done</option>
                                            </select>
                                        </form>
                                    </div>

                                    {{-- Control 2: Film Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Film Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="film_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="No Film" @selected(in_array($order->film_status, ['No Film', 'no_film']))>No Film</option>
                                                <option value="Ordering" @selected(in_array($order->film_status, ['Ordering', 'ordering']))>Ordering</option>
                                                <option value="Have Film" @selected(in_array($order->film_status, ['Have Film', 'have_film']))>Have Film</option>
                                                <option value="Done" @selected(in_array($order->film_status, ['Done', 'done']))>Done</option>
                                            </select>
                                        </form>
                                    </div>

                                    {{-- Control 3: Payment Status --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Payment Status</label>
                                        <form action="{{ route('orders.status', $order) }}" method="POST">
                                            @csrf
                                            <select name="payment_status" onchange="this.form.submit()" class="w-full h-9 rounded-lg border border-zinc-200 bg-white px-3 text-xs text-zinc-700 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 cursor-pointer shadow-2xs appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                                                <option value="Not Yet" @selected(in_array($order->payment_status, ['Not Yet', 'not_yet']))>Not Yet</option>
                                                <option value="Partial" @selected(in_array($order->payment_status, ['Partial', 'partial']))>Partial</option>
                                                <option value="Paid" @selected(in_array($order->payment_status, ['Paid', 'paid']))>Paid</option>
                                            </select>
                                        </form>
                                    </div>

                                @endif

                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($groups->every(fn ($g) => $g->isEmpty()))
        <div class="bg-white border border-zinc-200 rounded-xl p-8 shadow-sm text-center">
            <p class="text-xs text-zinc-400">Nothing in the pipeline right now — every order is delivered and paid.</p>
        </div>
    @endif
</div>
@endsection