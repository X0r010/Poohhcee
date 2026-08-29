@extends('layouts.app')

@section('title', 'Edit Order #' . $order->order_number)

@section('header-actions')
    <a href="{{ route('orders.show', $order) }}"
       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">
        Cancel
    </a>
@endsection

@section('content')
<div class="w-full" x-data="{ openDeleteModal: false }">
    <form action="{{ route('orders.update', $order) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- ── Header Info Banner ────────────────────────────────────────── --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-zinc-100 border border-zinc-200/60 flex items-center justify-center text-zinc-900 font-bold text-sm shrink-0">
                    #{{ substr($order->order_number, -3) }}
                </div>
                <div>
                    <h1 class="text-base font-bold text-zinc-900">Editing Order #{{ $order->order_number }}</h1>
                    <p class="text-xs text-zinc-500">Inventory will sync automatically if you change shirt statuses or specs.</p>
                </div>
            </div>

            <button type="submit"
                    class="inline-flex items-center justify-center h-9 px-5 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors shrink-0">
                Save Changes
            </button>
        </div>

        {{-- ── Main Form Grid ────────────────────────────────────────────── --}}
        <div class="grid lg:grid-cols-2 gap-5">

            {{-- ── 1. Customer Information ───────────────────────────────── --}}
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs space-y-4">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider pb-2 border-b border-zinc-100">Customer Details</h2>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Customer Handle / Name *</label>
                    <input type="text" name="customer_handle" value="{{ old('customer_handle', $order->customer_handle) }}" required
                           class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Phone Number</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}"
                               class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Order Source *</label>
                        <select name="source" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['TikTok', 'Instagram', 'Website', 'Walk-in', 'Other'] as $src)
                                <option value="{{ $src }}" {{ old('source', $order->source) === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Location / Address</label>
                    <input type="text" name="customer_location" value="{{ old('customer_location', $order->customer_location) }}"
                           class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Order Date *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}" required
                           class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                </div>
            </div>

            {{-- ── 2. Item Specifications ───────────────────────────────── --}}
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs space-y-4">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider pb-2 border-b border-zinc-100">Item Specifications</h2>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Design *</label>
                    <select name="design_id" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                        @foreach($collections as $collection)
                            <optgroup label="{{ $collection->name }}">
                                @foreach($collection->designs as $design)
                                    <option value="{{ $design->id }}" {{ old('design_id', $order->design_id) == $design->id ? 'selected' : '' }}>
                                        {{ $design->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Shirt Type *</label>
                        <select name="shirt_type_id" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach($shirtTypes as $type)
                                <option value="{{ $type->id }}" {{ old('shirt_type_id', $order->shirt_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Size *</label>
                        <select name="size" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['S', 'M', 'L', 'XL', '2XL', '3XL'] as $size)
                                <option value="{{ $size }}" {{ old('size', $order->size) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Color *</label>
                        <select name="color" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach($shirtColors as $shirtColor)
                                <option value="{{ $shirtColor->name }}" {{ old('color', $order->color) === $shirtColor->name ? 'selected' : '' }}>
                                    {{ $shirtColor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── 3. Production Status (MANUAL OVERRIDE) ───────────────── --}}
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs space-y-4">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider pb-2 border-b border-zinc-100">Production Status Overrides</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Shirt Status *</label>
                        <select name="shirt_status" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['Not Yet', 'Buying', 'Bought', 'Done'] as $status)
                                <option value="{{ $status }}" {{ old('shirt_status', $order->shirt_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Film Status *</label>
                        <select name="film_status" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['No Film', 'Have Film', 'Printed', 'Done'] as $status)
                                <option value="{{ $status }}" {{ old('film_status', $order->film_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Print Status *</label>
                        <select name="print_status" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['Pending', 'Printing', 'Printed', 'Done'] as $status)
                                <option value="{{ $status }}" {{ old('print_status', $order->print_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Delivery Status *</label>
                        <select name="delivery_status" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled'] as $status)
                                <option value="{{ $status }}" {{ old('delivery_status', $order->delivery_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── 4. Financials ───────────────────────────────────────── --}}
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs space-y-4">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider pb-2 border-b border-zinc-100">Financial Setup</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Base Price ($) *</label>
                        <input type="number" step="0.01" name="base_price" value="{{ old('base_price', $order->base_price) }}" required
                               class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Delivery Fee ($)</label>
                        <input type="number" step="0.01" name="delivery_fee" value="{{ old('delivery_fee', $order->delivery_fee ?? 0) }}"
                               class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black font-mono">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Payment Status</label>
                        <select name="payment_status" required class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                            @foreach(['Not Yet', 'Partial', 'Paid'] as $status)
                                <option value="{{ $status }}" {{ old('payment_status', $order->payment_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Payment Method</label>
                        <input type="text" name="payment_method" value="{{ old('payment_method', $order->payment_method) }}" placeholder="e.g. ABA Bank / Cash"
                               class="w-full h-9 px-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black">
                    </div>
                </div>
            </div>

            {{-- ── 5. Notes ────────────────────────────────────────────── --}}
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs lg:col-span-2 space-y-2">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider pb-2 border-b border-zinc-100">Order Notes</h2>
                <textarea name="notes" rows="4" placeholder="Add optional details..."
                          class="w-full p-3 rounded-lg border border-zinc-200 text-xs focus:ring-1 focus:ring-black focus:border-black leading-relaxed">{{ old('notes', $order->notes) }}</textarea>
            </div>

            {{-- ── 6. Danger Zone (Delete Order) ─────────────────────── --}}
            <div class="bg-red-50/50 border border-red-200/70 rounded-xl p-5 shadow-2xs lg:col-span-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xs font-bold text-red-900 uppercase tracking-wider">Danger Zone</h2>
                    <p class="text-xs text-red-600/80 mt-0.5">Permanently delete Order #{{ $order->order_number }} and all associated production records.</p>
                </div>
                <button type="button" @click="openDeleteModal = true"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-2xs transition-colors shrink-0">
                    Delete Order
                </button>
            </div>

        </div>
    </form>

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
                        Are you sure you want to delete this order? This action is permanent and cannot be undone.
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