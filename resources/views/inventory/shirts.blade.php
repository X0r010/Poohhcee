@extends('layouts.app')

@section('title', 'Shirt Inventory')

@section('content')
@php
    $shirtTypes = $shirtTypes ?? \App\Models\ShirtType::all();
    $shirtColors = $shirtColors ?? \App\Models\ShirtColor::all();
    $shirts = $shirts ?? collect();
    $printedShirts = $printedShirts ?? [];
@endphp

<div class="space-y-4 sm:space-y-6" x-data="{ 
    modalOpen: false, 
    itemName: '', 
    qtyAvailable: 0, 
    deleteRoute: '', 
    subtractRoute: '' 
}">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        {{-- ── Add / Restock ───────────────────────────────────────── --}}
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs h-fit space-y-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Add / Restock Shirts</h2>

            {{-- Managed Shirt Types --}}
            <div class="rounded-lg bg-zinc-50 border border-zinc-200 p-3 sm:p-3.5 space-y-2.5">
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Shirt Types</p>
                <div class="flex flex-wrap gap-1.5 sm:gap-2">
                    @forelse ($shirtTypes as $type)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-700">
                            {{ $type->name }}
                            <form method="POST" action="{{ route('shirt-types.destroy', $type) }}" class="inline flex items-center">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-zinc-400 hover:text-rose-600 text-sm leading-none">&times;</button>
                            </form>
                        </span>
                    @empty
                        <p class="text-xs text-zinc-400">No shirt types yet — add one below.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('shirt-types.store') }}" class="flex flex-col sm:flex-row gap-2 pt-1">
                    @csrf
                    <input type="text" name="name" required placeholder="Add new type e.g. Heavy Weight..."
                           class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                    <button type="submit" class="h-9 sm:h-8 px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs whitespace-nowrap">+ Add Type</button>
                </form>
            </div>

            {{-- Managed Shirt Colors --}}
            <div class="rounded-lg bg-zinc-50 border border-zinc-200 p-3 sm:p-3.5 space-y-2.5">
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Shirt Colors</p>
                <div class="flex flex-wrap gap-1.5 sm:gap-2">
                    @forelse ($shirtColors as $colorItem)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-700">
                            {{ $colorItem->name }}
                            <form method="POST" action="{{ route('shirt-colors.destroy', $colorItem) }}" class="inline flex items-center">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-zinc-400 hover:text-rose-600 text-sm leading-none">&times;</button>
                            </form>
                        </span>
                    @empty
                        <p class="text-xs text-zinc-400">No shirt colors yet — add one below.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('shirt-colors.store') }}" class="flex flex-col sm:flex-row gap-2 pt-1">
                    @csrf
                    <input type="text" name="name" required placeholder="Add new color e.g. Black, Pepper..."
                           class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                    <button type="submit" class="h-9 sm:h-8 px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs whitespace-nowrap">+ Add Color</button>
                </form>
            </div>

            {{-- Add Stock Form --}}
            <form method="POST" action="{{ route('inventory.shirts.add') }}" class="pt-2">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Shirt Type</label>
                        <select name="type" required class="w-full rounded-lg border border-zinc-200 pl-3 pr-8 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                            <option value="">Select type...</option>
                            @foreach ($shirtTypes as $type)
                                <option value="{{ $type->name }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Size</label>
                        <select name="size" required class="w-full rounded-lg border border-zinc-200 pl-3 pr-8 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                            @foreach (['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'] as $s)
                                <option value="{{ $s }}" {{ $s === 'M' ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Color</label>
                        <select name="color" required class="w-full rounded-lg border border-zinc-200 pl-3 pr-8 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                            <option value="">Select color...</option>
                            @foreach ($shirtColors as $c)
                                <option value="{{ $c->name }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Quantity</label>
                        <input type="number" name="quantity" min="1" value="1" required class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Cost per Unit ($)</label>
                        <input type="number" step="0.01" name="cost_per_unit" required placeholder="0.00" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Vendor</label>
                        <input type="text" name="vendor" placeholder="Supplier name..." class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="Any notes..." class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900">
                </div>
                <button type="submit" class="w-full sm:w-auto h-10 sm:h-9 px-5 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">Add to Inventory</button>
            </form>
        </div>

        {{-- ── Stock Summary ───────────────────────────────────────── --}}
        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs text-center sm:text-left">
                    <p class="text-[11px] sm:text-xs text-zinc-500 mb-0.5">In Stock</p>
                    <p class="text-lg sm:text-xl font-bold text-zinc-900">{{ $summary['in_stock'] ?? 0 }}</p>
                    <p class="text-[10px] sm:text-[11px] text-zinc-400">available</p>
                </div>
                <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs text-center sm:text-left">
                    <p class="text-[11px] sm:text-xs text-zinc-500 mb-0.5">Reserved</p>
                    <p class="text-lg sm:text-xl font-bold text-zinc-900">{{ $summary['reserved'] ?? 0 }}</p>
                    <p class="text-[10px] sm:text-[11px] text-zinc-400">for orders</p>
                </div>
                <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs text-center sm:text-left">
                    <p class="text-[11px] sm:text-xs text-zinc-500 mb-0.5">Used</p>
                    <p class="text-lg sm:text-xl font-bold text-zinc-900">{{ $summary['used'] ?? 0 }}</p>
                    <p class="text-[10px] sm:text-[11px] text-zinc-400">printed</p>
                </div>
            </div>
            
            <div class="bg-white border border-zinc-200/80 rounded-xl p-3.5 sm:p-4 shadow-2xs flex justify-between items-center">
                <p class="text-xs text-zinc-500">Stock Value</p>
                <p class="text-sm font-bold text-zinc-900">${{ number_format($summary['stock_value'] ?? 0, 2) }}</p>
            </div>
            
            @if (($summary['low_stock_count'] ?? 0) > 0)
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 sm:p-4">
                    <p class="text-xs font-medium text-rose-700 flex items-center gap-1.5">
                        <span>⚠</span> {{ $summary['low_stock_count'] }} item(s) low on stock (&le;2)
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Current Inventory (Divided by Shirt Type & White/Black Colors) ──────────────────────-- --}}
    @php
        $groupedShirts = collect($shirts)->groupBy(function($item) use ($shirtTypes) {
            return optional($item->shirtType)->name 
                ?? $shirtTypes->firstWhere('id', $item->shirt_type_id)?->name 
                ?? $shirtTypes->firstWhere('id', $item->type)?->name 
                ?? $shirtTypes->firstWhere('name', $item->type)?->name 
                ?? $item->type 
                ?? $item->shirt_type 
                ?? 'Uncategorized';
        });
    @endphp

    <div class="space-y-6 pt-2">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Current Inventory by Shirt Type</h2>

        @forelse ($groupedShirts as $typeName => $typeShirts)
            @php
                $colorGroups = [
                    'White' => $typeShirts->filter(fn($s) => strtolower(trim($s->color)) === 'white'),
                    'Black' => $typeShirts->filter(fn($s) => strtolower(trim($s->color)) === 'black'),
                    'Other Colors' => $typeShirts->reject(fn($s) => in_array(strtolower(trim($s->color)), ['white', 'black'])),
                ];
            @endphp

            <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
                {{-- Shirt Type Header --}}
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-zinc-900 inline-block"></span>
                        <h3 class="text-sm font-bold text-zinc-900">{{ $typeName }}</h3>
                    </div>
                    <div class="text-xs text-zinc-500 font-medium">
                        Total Available: <span class="font-bold text-zinc-900">{{ $typeShirts->sum('quantity') }}</span>
                    </div>
                </div>

                {{-- Side-by-Side White & Black Layout --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    @foreach (['White', 'Black'] as $cName)
                        @php $cList = $colorGroups[$cName]; @endphp
                        <div class="border border-zinc-200 rounded-xl p-3.5 bg-zinc-50/40 space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60">
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-800">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $cName === 'White' ? 'bg-white border border-zinc-400' : 'bg-zinc-900' }} inline-block"></span>
                                    {{ $cName }} Shirts
                                </span>
                                <span class="text-[11px] font-semibold text-zinc-500">In Stock: {{ $cList->sum('quantity') }}</span>
                            </div>

                            @if ($cList->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left whitespace-nowrap">
                                        <thead>
                                            <tr class="text-zinc-400 border-b border-zinc-200/60 text-[11px]">
                                                <th class="font-medium pb-1.5">Size</th>
                                                <th class="font-medium pb-1.5">Stock</th>
                                                <th class="font-medium pb-1.5">Res.</th>
                                                <th class="font-medium pb-1.5">Used</th>
                                                <th class="font-medium pb-1.5">Cost</th>
                                                <th class="font-medium pb-1.5 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-100">
                                            @foreach ($cList as $shirt)
                                                <tr x-data="{ editing: false }" class="hover:bg-white/80 transition-colors">
                                                    <td class="py-2 font-bold text-zinc-800">{{ $shirt->size }}</td>
                                                    <td class="py-2"><span class="badge {{ $shirt->quantity <= 2 ? 'badge-missing-both' : 'badge-unknown' }} font-bold">{{ $shirt->quantity }}</span></td>
                                                    <td class="py-2"><span class="badge badge-unknown">{{ $shirt->reserved_quantity }}</span></td>
                                                    <td class="py-2"><span class="badge badge-ready">{{ $shirt->used_quantity }}</span></td>
                                                    <td class="py-2 text-zinc-600" x-show="!editing">${{ number_format($shirt->cost_per_unit, 2) }}</td>
                                                    <td class="py-2 text-right" x-show="!editing">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <button @click="editing = true" class="text-zinc-500 hover:text-zinc-900 font-medium">Edit</button>
                                                            <button type="button" 
                                                                @click="
                                                                    itemName = '{{ addslashes($shirt->type ?? $shirt->shirt_type ?? 'Shirt') }} ({{ $shirt->size }}/{{ $shirt->color }})';
                                                                    qtyAvailable = {{ $shirt->quantity }};
                                                                    subtractRoute = '{{ route('inventory.shirts.removeUnit', $shirt) }}';
                                                                    deleteRoute = '{{ route('inventory.shirts.delete', $shirt) }}';
                                                                    modalOpen = true;
                                                                " 
                                                                class="text-rose-500 hover:text-rose-700 font-medium">-1 / Delete</button>
                                                        </div>
                                                    </td>
                                                    <td colspan="2" x-show="editing" class="py-1">
                                                        <form method="POST" action="{{ route('inventory.shirts.update', $shirt) }}" class="flex items-center gap-1 justify-end">
                                                            @csrf @method('PUT')
                                                            <input type="number" step="0.01" name="cost_per_unit" value="{{ $shirt->cost_per_unit }}" class="w-16 rounded border border-zinc-200 px-1.5 py-0.5 text-xs bg-white">
                                                            <button type="submit" class="text-emerald-600 font-semibold text-xs ml-1">Save</button>
                                                            <button type="button" @click="editing = false" class="text-zinc-400 hover:text-zinc-600 text-xs ml-1">✕</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-xs text-zinc-400 py-4 text-center italic">No {{ $cName }} shirts stocked for {{ $typeName }}.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Other Colors (If present) --}}
                @if ($colorGroups['Other Colors']->count() > 0)
                    <div class="border border-zinc-200 rounded-xl p-3.5 bg-zinc-50/40 space-y-3 mt-4">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                                Other Colors
                            </span>
                            <span class="text-[11px] font-semibold text-zinc-500">In Stock: {{ $colorGroups['Other Colors']->sum('quantity') }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left whitespace-nowrap">
                                <thead>
                                    <tr class="text-zinc-400 border-b border-zinc-200/60 text-[11px]">
                                        <th class="font-medium pb-1.5">Color</th>
                                        <th class="font-medium pb-1.5">Size</th>
                                        <th class="font-medium pb-1.5">Stock</th>
                                        <th class="font-medium pb-1.5">Res.</th>
                                        <th class="font-medium pb-1.5">Used</th>
                                        <th class="font-medium pb-1.5">Cost</th>
                                        <th class="font-medium pb-1.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach ($colorGroups['Other Colors'] as $shirt)
                                        <tr x-data="{ editing: false }" class="hover:bg-white/80 transition-colors">
                                            <td class="py-2 font-medium text-zinc-700">{{ $shirt->color }}</td>
                                            <td class="py-2 font-bold text-zinc-800">{{ $shirt->size }}</td>
                                            <td class="py-2"><span class="badge {{ $shirt->quantity <= 2 ? 'badge-missing-both' : 'badge-unknown' }} font-bold">{{ $shirt->quantity }}</span></td>
                                            <td class="py-2"><span class="badge badge-unknown">{{ $shirt->reserved_quantity }}</span></td>
                                            <td class="py-2"><span class="badge badge-ready">{{ $shirt->used_quantity }}</span></td>
                                            <td class="py-2 text-zinc-600" x-show="!editing">${{ number_format($shirt->cost_per_unit, 2) }}</td>
                                            <td class="py-2 text-right" x-show="!editing">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button @click="editing = true" class="text-zinc-500 hover:text-zinc-900 font-medium">Edit</button>
                                                    <button type="button" 
                                                        @click="
                                                            itemName = '{{ addslashes($shirt->type ?? $shirt->shirt_type ?? 'Shirt') }} ({{ $shirt->size }}/{{ $shirt->color }})';
                                                            qtyAvailable = {{ $shirt->quantity }};
                                                            subtractRoute = '{{ route('inventory.shirts.removeUnit', $shirt) }}';
                                                            deleteRoute = '{{ route('inventory.shirts.delete', $shirt) }}';
                                                            modalOpen = true;
                                                        " 
                                                        class="text-rose-500 hover:text-rose-700 font-medium">-1 / Delete</button>
                                                </div>
                                            </td>
                                            <td colspan="2" x-show="editing" class="py-1">
                                                <form method="POST" action="{{ route('inventory.shirts.update', $shirt) }}" class="flex items-center gap-1 justify-end">
                                                    @csrf @method('PUT')
                                                    <input type="number" step="0.01" name="cost_per_unit" value="{{ $shirt->cost_per_unit }}" class="w-16 rounded border border-zinc-200 px-1.5 py-0.5 text-xs bg-white">
                                                    <button type="submit" class="text-emerald-600 font-semibold text-xs ml-1">Save</button>
                                                    <button type="button" @click="editing = false" class="text-zinc-400 hover:text-zinc-600 text-xs ml-1">✕</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        @empty
            <div class="bg-white border border-zinc-200/80 rounded-xl p-8 text-center text-zinc-400 text-xs">
                No shirt inventory yet — add some above.
            </div>
        @endforelse
    </div>

    {{-- ── Printed Shirts Inventory ────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Printed Shirts Inventory</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left sm:whitespace-nowrap">
                <thead class="hidden sm:table-header-group">
                    <tr class="text-zinc-400 border-b border-zinc-100">
                        <th class="font-medium pb-2.5">Collection / Design</th>
                        <th class="font-medium pb-2.5">Type</th>
                        <th class="font-medium pb-2.5">Size</th>
                        <th class="font-medium pb-2.5">Color</th>
                        <th class="font-medium pb-2.5">In Stock</th>
                        <th class="font-medium pb-2.5">Date Added</th>
                        <th class="font-medium pb-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 sm:divide-zinc-50">
                    @forelse ($printedShirts as $printed)
                        <tr class="block sm:table-row p-3.5 mb-3 sm:mb-0 rounded-xl border border-zinc-200/80 sm:border-0 bg-white hover:bg-zinc-50/50 transition-colors shadow-2xs sm:shadow-none space-y-1.5 sm:space-y-0">
                            
                            <td class="block sm:table-cell sm:py-2.5 font-semibold text-zinc-800">
                                {{ optional($printed->design->collection)->name ?? '—' }} &rarr; {{ optional($printed->design)->name ?? '—' }}
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-zinc-700 font-medium">
                                <span class="sm:hidden text-zinc-400 font-normal">Type: </span>
                                {{ 
                                    optional($printed->shirtType)->name 
                                    ?? optional($printed->shirt)->type 
                                    ?? $shirtTypes->firstWhere('id', $printed->shirt_type_id)?->name 
                                    ?? $shirtTypes->firstWhere('id', $printed->shirt_type)?->name 
                                    ?? $shirtTypes->firstWhere('name', $printed->shirt_type)?->name 
                                    ?? $shirtTypes->firstWhere('name', $printed->type)?->name 
                                    ?? $printed->shirt_type 
                                    ?? $printed->type 
                                    ?? '—' 
                                }}
                            </td>

                            <td class="inline-block sm:table-cell sm:py-2.5 mr-3 sm:mr-0">
                                <span class="px-2 py-0.5 rounded border border-zinc-200 font-medium bg-zinc-50 text-[11px]">
                                    {{ $printed->size ?? '—' }}
                                </span>
                            </td>

                            <td class="inline-block sm:table-cell sm:py-2.5 text-zinc-700">
                                {{ $printed->color ?? '—' }}
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 pt-1 sm:pt-0">
                                <span class="sm:hidden text-zinc-400 mr-1">In Stock:</span>
                                <span class="font-bold {{ $printed->quantity == 0 ? 'text-rose-600' : 'text-zinc-900' }}">
                                    {{ $printed->quantity }}
                                </span>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-zinc-500">
                                <span class="sm:hidden text-zinc-400 mr-1">Added:</span>
                                {{ $printed->created_at ? $printed->created_at->format('M d, Y') : '—' }}
                            </td>

                            <td class="block sm:table-cell pt-2 sm:pt-2.5 border-t border-zinc-100 sm:border-t-0 text-left sm:text-right">
                                <form method="POST" action="{{ route('inventory.printed-shirts.delete', $printed) }}" class="inline">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 font-medium">Delete Entry</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-400 text-xs">No printed shirts in inventory yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── CUSTOM ACTION POPUP MODAL ────────────────────────────── --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div @click.away="modalOpen = false" class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 border border-zinc-200">
            <h3 class="text-sm font-bold text-zinc-900 mb-1" x-text="`Modify Stock: ${itemName}`"></h3>
            <p class="text-xs text-zinc-500 mb-4">Current available count: <strong x-text="qtyAvailable" class="text-zinc-800"></strong></p>

            <div class="space-y-2">
                {{-- Subtract 1 Option --}}
                <form :action="subtractRoute" method="POST">
                    @csrf
                    <button type="submit" class="w-full h-10 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold transition-colors flex items-center justify-center gap-2 border border-zinc-200">
                        <span>Deduct 1 Unit (-1)</span>
                    </button>
                </form>

                {{-- Delete Entire Item Option --}}
                <form :action="deleteRoute" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full h-10 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-2 shadow-xs">
                        <span>Delete All / Clear Entry</span>
                    </button>
                </form>
            </div>

            <button type="button" @click="modalOpen = false" class="w-full mt-3 text-center text-xs font-medium text-zinc-400 hover:text-zinc-600 py-1">
                Cancel
            </button>
        </div>
    </div>

</div>
@endsection