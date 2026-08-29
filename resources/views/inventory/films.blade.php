@extends('layouts.app')

@section('title', 'DTF Film Inventory')

@section('content')
<div class="space-y-5" x-data="{ 
    modalOpen: false, 
    itemName: '', 
    qtyAvailable: 0, 
    deleteRoute: '', 
    subtractRoute: '' 
}">

    {{-- ── Update Film Stock ───────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Update Film Stock</h2>
            <p class="text-[11px] text-zinc-400">Select a design and set how many prints you have</p>
        </div>
        <form method="POST" action="{{ route('inventory.films.add') }}" x-data="{ collectionId: '' }">
            @csrf
            <div class="grid sm:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Artist Collection</label>
                    <select x-model="collectionId" class="w-full rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                        <option value="">Select artist...</option>
                        @foreach ($collections as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Design</label>
                    <select name="design_id" required class="w-full rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                        <option value="">Select design...</option>
                        @foreach ($collections as $c)
                            @foreach ($c->designs as $d)
                                <option value="{{ $d->id }}" x-show="collectionId == {{ $c->id }} || collectionId === ''">{{ $c->name }} — {{ $d->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Side</label>
                    <select name="side" required class="w-full rounded-lg border border-zinc-200 py-2 pl-3 pr-8 text-xs focus:outline-none focus:border-zinc-900 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23374151%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[right_0.75rem_center] bg-no-repeat">
                        <option value="front">Front</option>
                        <option value="back">Back</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Prints to Add</label>
                    <input type="number" name="prints_available" min="1" value="10" required
                           class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Cost per Print ($)</label>
                    <input type="number" step="0.01" name="cost_per_print" value="1.50" required
                           class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                </div>
            </div>
            <div class="grid sm:grid-cols-5 gap-4 items-end">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Notes</label>
                    <input type="text" name="notes" placeholder="Optional..."
                           class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                </div>
                <button type="submit" class="h-9 px-5 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">Save Film Stock</button>
            </div>
        </form>
    </div>

    {{-- ── Stock Summary ───────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 shadow-2xs">
            <p class="text-xs text-zinc-500 mb-1">Available</p>
            <p class="text-xl font-bold">{{ $summary['available'] }}</p>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 shadow-2xs">
            <div class="text-xs text-zinc-500 font-medium">Reserved</div>
            <div class="text-xl font-bold text-zinc-900 mt-1">
                {{ $summary['reserved'] }}
            </div>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 shadow-2xs">
            <p class="text-xs text-zinc-500 mb-1">Used</p>
            <p class="text-xl font-bold">{{ $summary['used'] }}</p>
        </div>
        <!-- Clickable Low Stock Card -->
        <button 
            onclick="document.getElementById('lowStockModal').classList.remove('hidden')" 
            type="button"
            class="w-full text-left bg-white border border-red-200 hover:border-red-400 transition-all duration-150 rounded-xl p-4 shadow-2xs group cursor-pointer"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs text-red-600 font-semibold uppercase tracking-wider">Low Stock</span>
                <span class="text-[11px] text-red-500 font-medium group-hover:underline">Click to view &rarr;</span>
            </div>
            <div class="text-xl font-bold text-red-600 mt-1">
                {{ $summary['low_stock_count'] }}
            </div>
        </button>
    </div>

    {{-- ── Grouped by artist ────────────────────────────────────── --}}
    @foreach ($collections as $collection)
        @php
            $filmDesigns = $collection->unique_film_designs ?? $collection->designs;
        @endphp

        @if ($filmDesigns->count() > 0)
            <div class="bg-white border border-zinc-200/80 rounded-xl p-5 shadow-2xs">
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">{{ $collection->name }}</h3>
                    <span class="badge badge-unknown">{{ $filmDesigns->count() }} physical film {{ Str::plural('type', $filmDesigns->count()) }}</span>
                </div>
                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach ($filmDesigns as $design)
                        @php
                            $artwork = $design->printArtwork;
                            $front = $artwork?->films->where('side', 'front')->first();
                            $back = $artwork?->films->where('side', 'back')->first();

                            $frontAvailable = $design->has_front && (($front->prints_available ?? 0) > 0);
                            $backAvailable  = $design->has_back && (($back->prints_available ?? 0) > 0);

                            $isComplete = (!$design->has_front || $frontAvailable) && (!$design->has_back || $backAvailable);
                            $missingFront = $design->has_front && !$frontAvailable;
                            $missingBack  = $design->has_back && !$backAvailable;

                            $sharedList = $design->shared_designs ?? collect([$design]);
                        @endphp
                        <div class="rounded-lg border p-3.5 {{ !$isComplete ? 'border-amber-200 bg-amber-50/20' : 'border-zinc-200 bg-white' }}">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-xs font-semibold text-zinc-800">{{ $design->name }}</p>
                                    
                                    {{-- Shared Films Badge indicator --}}
                                    @if ($sharedList->count() > 1)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach ($sharedList as $shared)
                                                <span class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 border border-zinc-200">
                                                    {{ $shared->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="shrink-0 ml-2">
                                    @if ($isComplete)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 uppercase">Ready</span>
                                    @elseif ($missingFront && $missingBack)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 uppercase">Missing Both</span>
                                    @elseif ($missingFront)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">Missing Front</span>
                                    @elseif ($missingBack)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">Missing Back</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Front Side --}}
                            @if ($design->has_front)
                                <div class="mb-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider">Front</p>
                                        @if ($front)
                                            <button type="button"
                                                @click="
                                                    itemName = '{{ addslashes($design->name) }} (Front)';
                                                    qtyAvailable = {{ $front->prints_available ?? 0 }};
                                                    subtractRoute = '{{ route('inventory.films.removeUnit', $front) }}';
                                                    deleteRoute = '{{ route('inventory.films.delete', $front) }}';
                                                    modalOpen = true;
                                                "
                                                class="text-[10px] font-semibold px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded transition-colors border border-rose-200">
                                                Manage / Delete
                                            </button>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-3 gap-1 text-center">
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold {{ ($front->prints_available ?? 0) <= 2 ? 'text-rose-600' : 'text-zinc-900' }}">{{ $front->prints_available ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">avail</p>
                                        </div>
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold text-zinc-600">{{ $front->reserved_quantity ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">reserved</p>
                                        </div>
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold text-zinc-600">{{ $front->used_quantity ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">used</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Back Side --}}
                            @if ($design->has_back)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider">Back</p>
                                        @if ($back)
                                            <button type="button"
                                                @click="
                                                    itemName = '{{ addslashes($design->name) }} (Back)';
                                                    qtyAvailable = {{ $back->prints_available ?? 0 }};
                                                    subtractRoute = '{{ route('inventory.films.removeUnit', $back) }}';
                                                    deleteRoute = '{{ route('inventory.films.delete', $back) }}';
                                                    modalOpen = true;
                                                "
                                                class="text-[10px] font-semibold px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded transition-colors border border-rose-200">
                                                Manage / Delete
                                            </button>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-3 gap-1 text-center">
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold {{ ($back->prints_available ?? 0) <= 2 ? 'text-rose-600' : 'text-zinc-900' }}">{{ $back->prints_available ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">avail</p>
                                        </div>
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold text-zinc-600">{{ $back->reserved_quantity ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">reserved</p>
                                        </div>
                                        <div class="rounded bg-zinc-50 py-1.5">
                                            <p class="text-sm font-bold text-zinc-600">{{ $back->used_quantity ?? 0 }}</p>
                                            <p class="text-[8px] text-zinc-400">used</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <p class="text-[10px] text-zinc-400 mt-2">
                                ${{ number_format($front->cost_per_print ?? 0, 2) }}/print (front) · ${{ number_format($back->cost_per_print ?? 0, 2) }}/print (back)
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    {{-- ── CUSTOM ACTION POPUP MODAL ────────────────────────────── --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div @click.away="modalOpen = false" class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 border border-zinc-200">
            <h3 class="text-sm font-bold text-zinc-900 mb-1" x-text="`Modify Stock: ${itemName}`"></h3>
            <p class="text-xs text-zinc-500 mb-4">Current available count: <strong x-text="qtyAvailable" class="text-zinc-800"></strong></p>

            <div class="space-y-2">
                {{-- Subtract 1 Option --}}
                <form :action="subtractRoute" method="POST">
                    @csrf
                    <button type="submit" class="w-full h-9 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold transition-colors flex items-center justify-center gap-2 border border-zinc-200">
                        <span>Deduct 1 Unit (-1)</span>
                    </button>
                </form>

                {{-- Delete Entire Item Option --}}
                <form :action="deleteRoute" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full h-9 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-2 shadow-xs">
                        <span>Delete All / Clear Entry</span>
                    </button>
                </form>
            </div>

            <button type="button" @click="modalOpen = false" class="w-full mt-3 text-center text-xs font-medium text-zinc-400 hover:text-zinc-600">
                Cancel
            </button>
        </div>
    </div>

    <!-- Low Stock Films Modal -->
    <div id="lowStockModal" class="fixed inset-0 z-50 hidden bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[80vh] flex flex-col shadow-2xl overflow-hidden border border-zinc-200">
            
            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between bg-zinc-50/50">
                <div>
                    <h3 class="text-lg font-bold text-zinc-900">Films to Print / Restock</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">All film designs with 2 or fewer physical prints available</p>
                </div>
                <button 
                    onclick="document.getElementById('lowStockModal').classList.add('hidden')" 
                    class="text-zinc-400 hover:text-zinc-700 p-1 rounded-lg transition"
                >
                    ✕
                </button>
            </div>

            <!-- Film List -->
            <div class="p-6 overflow-y-auto space-y-3 divide-y divide-zinc-100">
                @forelse($lowStockFilms as $film)
                    <div class="pt-3 first:pt-0 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm text-zinc-900">
                                {{ $film->design->collection->name ?? 'Collection' }} — {{ $film->design->name ?? 'Film Design' }}
                            </div>
                            <div class="text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                                <span class="px-1.5 py-0.5 bg-zinc-100 rounded text-zinc-700 font-medium capitalize">{{ $film->side }} side</span>
                                @if($film->shirt_color)
                                    <span class="text-zinc-400">• Color: {{ ucfirst($film->shirt_color) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                {{ $film->prints_available }} available
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500 text-sm">
                        🎉 All films are sufficiently stocked!
                    </div>
                @endforelse
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-zinc-100 bg-zinc-50/50 text-right">
                <button 
                    onclick="document.getElementById('lowStockModal').classList.add('hidden')" 
                    class="px-4 py-2 bg-zinc-900 text-white text-xs font-semibold rounded-lg hover:bg-zinc-800 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection