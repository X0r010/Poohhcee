@extends('layouts.app')

@section('title', 'Buy List & Print List')

@section('content')
<div class="space-y-6" x-data="buylist()" x-cloak>

    {{-- Page Header & View Tabs --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-zinc-900 tracking-tight">Buy List & Print List</h1>
            <p class="text-xs text-zinc-500">Filter orders to generate supplier messages and track stock.</p>
        </div>
        <div class="flex bg-zinc-100 p-1 rounded-lg w-full sm:w-auto border border-zinc-200">
            <button @click="tab = 'shirts'" 
                    :class="tab === 'shirts' ? 'bg-white shadow-xs text-zinc-900 font-bold' : 'text-zinc-500 hover:text-zinc-700 font-medium'"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-md text-xs transition-all text-center">
                👕 Shirts to Buy
            </button>
            <button @click="tab = 'films'" 
                    :class="tab === 'films' ? 'bg-white shadow-xs text-zinc-900 font-bold' : 'text-zinc-500 hover:text-zinc-700 font-medium'"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-md text-xs transition-all text-center">
                🎞️ DTF Films
            </button>
        </div>
    </div>

    {{-- SHIRTS TAB CONTENT --}}
    <div x-show="tab === 'shirts'" class="space-y-6">
        
        {{-- Filter Bar Card --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 shadow-2xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <select x-model="filters.collection" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Collections</option>
                    @foreach($collections as $col)
                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                    @endforeach
                </select>

                <select x-model="filters.status" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Statuses</option>
                    <option value="Not Yet">Not Yet</option>
                    <option value="Buying">Buying</option>
                    <option value="Bought">Bought / Done</option>
                </select>

                <select x-model="filters.color" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Colors</option>
                    <template x-for="color in uniqueColors" :key="color">
                        <option :value="color" x-text="color"></option>
                    </template>
                </select>

                <select x-model="filters.size" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Sizes</option>
                    <template x-for="size in uniqueSizes" :key="size">
                        <option :value="size" x-text="size"></option>
                    </template>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button @click="resetShirtFilters" class="w-full sm:w-auto px-4 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-600 rounded-lg text-xs font-semibold transition-colors">Clear Filters</button>
            </div>
        </div>

        {{-- Main Split Layout --}}
        <div class="grid lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/50">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Summary</h3>
                        <button @click="copyShirtText" x-text="copiedShirt ? 'Copied! ✓' : 'Copy Text'" 
                                :class="copiedShirt ? 'bg-emerald-600 text-white' : 'bg-zinc-900 text-white hover:bg-zinc-800'"
                                class="text-[10px] font-semibold px-3 py-1 rounded-md transition-all">
                        </button>
                    </div>
                    
                    <div class="overflow-y-auto max-h-[220px]">
                        <table class="w-full text-xs text-left">
                            <thead class="sticky top-0 bg-zinc-50 border-b border-zinc-100 text-zinc-400">
                                <tr>
                                    <th class="py-2.5 px-3 sm:px-4 font-medium">Type</th>
                                    <th class="py-2.5 px-3 sm:px-4 font-medium">Color</th>
                                    <th class="py-2.5 px-3 sm:px-4 font-medium">Size</th>
                                    <th class="py-2.5 px-3 sm:px-4 font-medium text-right">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50">
                                <template x-for="item in summaryList" :key="item.type + item.color + item.size">
                                    <tr class="hover:bg-zinc-50/50">
                                        <td class="py-2.5 px-3 sm:px-4 font-medium text-zinc-800" x-text="item.type"></td>
                                        <td class="py-2.5 px-3 sm:px-4 text-zinc-600" x-text="item.color"></td>
                                        <td class="py-2.5 px-3 sm:px-4 text-zinc-600" x-text="item.size"></td>
                                        <td class="py-2.5 px-3 sm:px-4 text-right font-bold text-indigo-600" x-text="item.qty"></td>
                                    </tr>
                                </template>
                                <tr x-show="summaryList.length === 0">
                                    <td colspan="4" class="py-6 text-center text-zinc-400 text-xs">No summary data available.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xs flex flex-col overflow-hidden">
                    <div class="px-4 py-3 border-b border-zinc-800 bg-black/20">
                        <h3 class="text-[11px] font-bold text-zinc-300 uppercase tracking-wider">Generated Supplier Text</h3>
                    </div>
                    <textarea readonly x-model="generatedShirtText" class="w-full h-[220px] bg-transparent text-emerald-400 font-mono text-xs p-4 focus:outline-none resize-none leading-relaxed"></textarea>
                </div>
            </div>

            <div class="lg:col-span-7 bg-white border border-zinc-200/80 rounded-xl shadow-2xs flex flex-col min-h-[350px] sm:h-[500px]">
                <div class="px-4 sm:px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/50 rounded-t-xl">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Orders Included</h3>
                    <span class="bg-zinc-200 text-zinc-700 rounded-full px-2.5 py-0.5 text-[10px] font-bold" x-text="`${filteredOrders.length} Orders`"></span>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 sm:p-0">
                    <table class="w-full text-xs text-left sm:whitespace-nowrap">
                        <thead class="sticky top-0 bg-white shadow-xs z-10 hidden sm:table-header-group">
                            <tr class="text-zinc-400 border-b border-zinc-100">
                                <th class="font-medium py-3 px-4">Order #</th>
                                <th class="font-medium py-3 px-4">Customer</th>
                                <th class="font-medium py-3 px-4">Design</th>
                                <th class="font-medium py-3 px-4">Type</th>
                                <th class="font-medium py-3 px-4">Size / Color</th>
                                <th class="font-medium py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50">
                            <template x-for="order in filteredOrders" :key="order.id">
                                <tr class="block sm:table-row p-3.5 mb-2.5 rounded-xl border border-zinc-200/80 sm:border-0 sm:p-0 sm:mb-0 sm:rounded-none bg-white hover:bg-zinc-50/50 transition-colors shadow-2xs sm:shadow-none">
                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 font-semibold text-zinc-800">
                                        <div class="flex items-center justify-between sm:block">
                                            <span x-text="'#' + order.order_number"></span>
                                            <span class="text-zinc-500 font-normal sm:hidden" x-text="'@' + order.customer_handle"></span>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell py-3 px-4 text-zinc-600" x-text="'@' + order.customer_handle"></td>
                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 font-medium text-zinc-700" x-text="order.design"></td>
                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 text-zinc-500">
                                        <span class="sm:hidden text-zinc-400 mr-1">Type:</span><span x-text="order.shirt_type"></span>
                                    </td>
                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 font-mono text-zinc-700">
                                        <span class="sm:hidden text-zinc-400 font-sans mr-1">Specs:</span><span x-text="`${order.size} / ${order.color}`"></span>
                                    </td>
                                    <td class="block sm:table-cell pt-2 sm:pt-0 py-1 sm:py-3 sm:px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold"
                                            :class="{
                                                'bg-amber-50 text-amber-600 border border-amber-200': order.shirt_status === 'Not Yet',
                                                'bg-blue-50 text-blue-600 border border-blue-200': order.shirt_status === 'Buying',
                                                'bg-emerald-50 text-emerald-600 border border-emerald-200': order.shirt_status === 'Bought' || order.shirt_status === 'Done'
                                            }"
                                            x-text="order.shirt_status"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredOrders.length === 0">
                                <td colspan="6" class="py-12 text-center text-zinc-400 text-xs">No orders match the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- FILMS TAB CONTENT --}}
    <div x-show="tab === 'films'" class="space-y-6">
        
        {{-- Film Filters Card --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 shadow-2xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select x-model="filmFilters.collection" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Collections</option>
                    @foreach($collections as $col)
                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                    @endforeach
                </select>

                <select x-model="filmFilters.status" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Film Statuses</option>
                    <option value="No Film">No Film (Need to Print/Buy)</option>
                    <option value="Ordering">Ordering</option>
                    <option value="Have Film">Have Film / Ready</option>
                </select>

                <select x-model="filmFilters.design" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-700 focus:outline-none focus:border-zinc-400">
                    <option value="">All Designs</option>
                    <template x-for="d in uniqueFilmDesigns" :key="d">
                        <option :value="d" x-text="d"></option>
                    </template>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button @click="resetFilmFilters" class="w-full sm:w-auto px-4 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-600 rounded-lg text-xs font-semibold transition-colors">Clear Film Filters</button>
            </div>
        </div>

        {{-- Split Layout for Film Buylist --}}
        <div class="grid lg:grid-cols-12 gap-6">
            
            {{-- Left Side: Summary & Text Box --}}
            <div class="lg:col-span-5 space-y-6">
                
                {{-- Film Prints Summary Table --}}
                <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/50">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">DTF Prints Needed</h3>
                        <button @click="copyFilmText" x-text="copiedFilm ? 'Copied! ✓' : 'Copy Text'" 
                                :class="copiedFilm ? 'bg-emerald-600 text-white' : 'bg-zinc-900 text-white hover:bg-zinc-800'"
                                class="text-[10px] font-semibold px-3 py-1 rounded-md transition-all">
                        </button>
                    </div>
                    
                    <div class="overflow-y-auto max-h-[220px]">
                        <table class="w-full text-xs text-left">
                            <thead class="sticky top-0 bg-zinc-50 border-b border-zinc-100 text-zinc-400">
                                <tr>
                                    <th class="py-2.5 px-4 font-medium">Design Name</th>
                                    <th class="py-2.5 px-4 font-medium">Side</th>
                                    <th class="py-2.5 px-4 font-medium text-right">Prints Needed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50">
                                <template x-for="item in filmSummaryList" :key="item.design + item.side">
                                    <tr class="hover:bg-zinc-50/50">
                                        <td class="py-2.5 px-4 font-semibold text-zinc-800" x-text="item.design"></td>
                                        <td class="py-2.5 px-4 text-zinc-600">
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold"
                                                  :class="item.side === 'Front' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200'"
                                                  x-text="item.side"></span>
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-bold text-indigo-600" x-text="item.qty"></td>
                                    </tr>
                                </template>
                                <tr x-show="filmSummaryList.length === 0">
                                    <td colspan="3" class="py-6 text-center text-zinc-400 text-xs">No film prints required for selected filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Generated Supplier Copy Text --}}
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xs flex flex-col overflow-hidden">
                    <div class="px-4 py-3 border-b border-zinc-800 bg-black/20">
                        <h3 class="text-[11px] font-bold text-zinc-300 uppercase tracking-wider">Generated Film Order Text</h3>
                    </div>
                    <textarea readonly x-model="generatedFilmText" class="w-full h-[220px] bg-transparent text-emerald-400 font-mono text-xs p-4 focus:outline-none resize-none leading-relaxed"></textarea>
                </div>

            </div>

            {{-- Right Side: Orders Included Table --}}
            <div class="lg:col-span-7 bg-white border border-zinc-200/80 rounded-xl shadow-2xs flex flex-col min-h-[350px] sm:h-[500px]">
                <div class="px-4 sm:px-5 py-4 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/50 rounded-t-xl">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider font-mono">Orders Needing Films</h3>
                    <span class="bg-zinc-200 text-zinc-700 rounded-full px-2.5 py-0.5 text-[10px] font-bold" x-text="`${filteredFilmOrders.length} Orders`"></span>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 sm:p-0">
                    <table class="w-full text-xs text-left sm:whitespace-nowrap">
                        <thead class="sticky top-0 bg-white shadow-xs z-10 hidden sm:table-header-group">
                            <tr class="text-zinc-400 border-b border-zinc-100">
                                <th class="font-medium py-3 px-4">Order #</th>
                                <th class="font-medium py-3 px-4">Design</th>
                                <th class="font-medium py-3 px-4">Print Sides</th>
                                <th class="font-medium py-3 px-4">Shirt Specs</th>
                                <th class="font-medium py-3 px-4">Film Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50">
                            <template x-for="order in filteredFilmOrders" :key="order.id">
                                <tr class="block sm:table-row p-3.5 mb-2.5 rounded-xl border border-zinc-200/80 sm:border-0 sm:p-0 sm:mb-0 sm:rounded-none bg-white hover:bg-zinc-50/50 transition-colors shadow-2xs sm:shadow-none">
                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 font-semibold text-zinc-800">
                                        <div class="flex items-center justify-between sm:block">
                                            <span x-text="'#' + order.order_number"></span>
                                            <span class="text-zinc-500 font-normal sm:hidden" x-text="'@' + order.customer_handle"></span>
                                        </div>
                                    </td>

                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 font-medium text-zinc-800" x-text="order.design"></td>

                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4">
                                        <div class="flex gap-1">
                                            <span x-show="order.has_front" class="bg-blue-50 text-blue-700 border border-blue-200 px-1.5 py-0.5 rounded text-[10px] font-medium">Front</span>
                                            <span x-show="order.has_back" class="bg-purple-50 text-purple-700 border border-purple-200 px-1.5 py-0.5 rounded text-[10px] font-medium">Back</span>
                                        </div>
                                    </td>

                                    <td class="block sm:table-cell py-0.5 sm:py-3 sm:px-4 text-zinc-500 font-mono">
                                        <span x-text="`${order.shirt_type} (${order.color} / ${order.size})`"></span>
                                    </td>

                                    <td class="block sm:table-cell pt-2 sm:pt-0 py-1 sm:py-3 sm:px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold"
                                            :class="{
                                                'bg-red-50 text-red-600 border border-red-200': order.film_status === 'No Film',
                                                'bg-blue-50 text-blue-600 border border-blue-200': order.film_status === 'Ordering',
                                                'bg-emerald-50 text-emerald-600 border border-emerald-200': order.film_status === 'Have Film' || order.film_status === 'Printed'
                                            }"
                                            x-text="order.film_status"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredFilmOrders.length === 0">
                                <td colspan="5" class="py-12 text-center text-zinc-400 text-xs">No active orders match film filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Alpine.js Logic --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('buylist', () => ({
        tab: 'shirts',
        allOrders: @json($orders),
        allFilms: @json($filmList),
        copiedShirt: false,
        copiedFilm: false,

        // Shirt Filters
        filters: {
            collection: '',
            status: 'Buying',
            color: '',
            size: ''
        },

        // Film Filters
        filmFilters: {
            collection: '',
            status: 'No Film',
            design: ''
        },

        get activeOrders() {
            return this.allOrders.filter(o => !o.printed_shirt_id);
        },

        // --- SHIRT METHODS ---
        get uniqueColors() {
            return [...new Set(this.activeOrders.map(o => o.color))].filter(Boolean).sort();
        },

        get uniqueSizes() {
            return [...new Set(this.activeOrders.map(o => o.size))].filter(Boolean);
        },

        get filteredOrders() {
            return this.activeOrders.filter(o => {
                if (this.filters.collection && String(o.collection_id) !== String(this.filters.collection)) return false;
                
                if (this.filters.status) {
                    if (this.filters.status === 'Bought') {
                        if (o.shirt_status !== 'Bought' && o.shirt_status !== 'Done') return false;
                    } else if (o.shirt_status !== this.filters.status) {
                        return false;
                    }
                }

                if (this.filters.color && o.color !== this.filters.color) return false;
                if (this.filters.size && o.size !== this.filters.size) return false;
                return true;
            });
        },

        get summaryList() {
            let map = {};
            this.filteredOrders.forEach(o => {
                let key = `${o.shirt_type}|${o.color}|${o.size}`;
                if (!map[key]) {
                    map[key] = { type: o.shirt_type, color: o.color, size: o.size, qty: 0 };
                }
                map[key].qty++;
            });
            return Object.values(map);
        },

        get generatedShirtText() {
            let orders = this.filteredOrders;
            if (orders.length === 0) return `No items found for current filters.`;

            let grouped = {};
            orders.forEach(o => {
                let type = o.shirt_type || 'Standard Tee';
                let color = o.color || 'Unknown';
                let size = o.size || 'Unknown';

                if (!grouped[type]) grouped[type] = {};
                if (!grouped[type][color]) grouped[type][color] = {};
                if (!grouped[type][color][size]) grouped[type][color][size] = 0;
                grouped[type][color][size]++;
            });

            let text = '';
            let isFirst = true;
            for (const [type, colors] of Object.entries(grouped)) {
                if (!isFirst) text += '\n';
                text += `${type}\n`;
                for (const [color, sizes] of Object.entries(colors)) {
                    let sizeStrs = [];
                    for (const [size, qty] of Object.entries(sizes)) {
                        sizeStrs.push(`${size}:${qty}`);
                    }
                    text += `- ${color} [${sizeStrs.join(', ')}]\n`;
                }
                isFirst = false;
            }

            text += `\nTotal: ${orders.length}`;
            return text;
        },

        resetShirtFilters() {
            this.filters.collection = '';
            this.filters.status = '';
            this.filters.color = '';
            this.filters.size = '';
        },

        copyShirtText() {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(this.generatedShirtText).then(() => {
                this.copiedShirt = true;
                setTimeout(() => this.copiedShirt = false, 2000);
            });
        },

        // --- FILM METHODS ---
        get uniqueFilmDesigns() {
            return [...new Set(this.activeOrders.map(o => o.design))].filter(Boolean).sort();
        },

        get filteredFilmOrders() {
            return this.activeOrders.filter(o => {
                if (this.filmFilters.collection && String(o.collection_id) !== String(this.filmFilters.collection)) return false;
                
                if (this.filmFilters.status) {
                    if (this.filmFilters.status === 'Have Film') {
                        if (o.film_status !== 'Have Film' && o.film_status !== 'Printed') return false;
                    } else if (o.film_status !== this.filmFilters.status) {
                        return false;
                    }
                }

                if (this.filmFilters.design && o.design !== this.filmFilters.design) return false;
                return true;
            });
        },

        get filmSummaryList() {
            let map = {};
            this.filteredFilmOrders.forEach(o => {
                if (o.has_front) {
                    let key = `${o.design}|Front`;
                    if (!map[key]) map[key] = { design: o.design, side: 'Front', qty: 0 };
                    map[key].qty++;
                }
                if (o.has_back) {
                    let key = `${o.design}|Back`;
                    if (!map[key]) map[key] = { design: o.design, side: 'Back', qty: 0 };
                    map[key].qty++;
                }
            });
            return Object.values(map);
        },

        get generatedFilmText() {
            let items = this.filmSummaryList;
            if (items.length === 0) return `No film prints required for current filters.`;

            let grouped = {};
            items.forEach(item => {
                if (!grouped[item.design]) grouped[item.design] = [];
                grouped[item.design].push(`${item.side}: ${item.qty}`);
            });

            let text = `🎞️ DTF PRINT ORDER\n------------------\n`;
            let totalPrints = 0;

            for (const [design, sides] of Object.entries(grouped)) {
                text += `${design}\n`;
                sides.forEach(sideStr => {
                    text += ` - ${sideStr}\n`;
                });
                text += '\n';
            }

            items.forEach(i => totalPrints += i.qty);
            text += `Total Film Prints: ${totalPrints}`;
            return text.trim();
        },

        resetFilmFilters() {
            this.filmFilters.collection = '';
            this.filmFilters.status = '';
            this.filmFilters.design = '';
        },

        copyFilmText() {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(this.generatedFilmText).then(() => {
                this.copiedFilm = true;
                setTimeout(() => this.copiedFilm = false, 2000);
            });
        }
    }));
});
</script>
@endsection