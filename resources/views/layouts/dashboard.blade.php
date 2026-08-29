@extends('layouts.app')

@section('title', 'Dashboard')

@section('header-actions')
    <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center h-9 px-3.5 sm:px-4 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">+ New Order</a>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-5 max-w-full overflow-x-hidden">

    {{-- ── 1. Top Stat Cards Row ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
        <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs hover:border-zinc-300 transition-colors min-w-0">
            <p class="text-[11px] sm:text-xs text-zinc-500 font-medium mb-1 truncate">Total Orders</p>
            <p class="text-lg sm:text-2xl font-bold tracking-tight text-zinc-900 truncate">{{ number_format($stats['total_orders']) }}</p>
            <p class="text-[10px] sm:text-[11px] text-zinc-400 mt-1 truncate">{{ $stats['orders_this_month'] }} this month</p>
        </div>

        <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs hover:border-zinc-300 transition-colors min-w-0">
            <p class="text-[11px] sm:text-xs text-zinc-500 font-medium mb-1 truncate">Pending</p>
            <p class="text-lg sm:text-2xl font-bold tracking-tight text-zinc-900 truncate">{{ number_format($stats['pending']) }}</p>
            <p class="text-[10px] sm:text-[11px] text-zinc-400 mt-1 truncate">not yet delivered</p>
        </div>

        <!-- Highlighted Black Card -->
        <div class="bg-black text-white rounded-xl p-3 sm:p-4 shadow-xs min-w-0">
            <p class="text-[11px] sm:text-xs text-zinc-400 font-medium mb-1 truncate">Ready to Print</p>
            <p class="text-lg sm:text-2xl font-bold tracking-tight text-white truncate">{{ number_format($stats['ready_to_print']) }}</p>
            <p class="text-[10px] sm:text-[11px] text-zinc-400 mt-1 truncate">shirt + film {{ $stats['ready_to_print'] > 0 ? '✓' : '' }}</p>
        </div>

        <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs hover:border-zinc-300 transition-colors min-w-0">
            <p class="text-[11px] sm:text-xs text-zinc-500 font-medium mb-1 truncate">Month Revenue</p>
            <p class="text-lg sm:text-2xl font-bold tracking-tight text-zinc-900 truncate">${{ number_format($stats['month_revenue'], 0) }}</p>
            <p class="text-[10px] sm:text-[11px] text-zinc-400 mt-1 truncate">${{ number_format($stats['month_expenses'], 0) }} expenses</p>
        </div>

        <div class="bg-white border border-zinc-200/80 rounded-xl p-3 sm:p-4 shadow-2xs hover:border-zinc-300 transition-colors col-span-2 sm:col-span-1 min-w-0">
            <p class="text-[11px] sm:text-xs text-zinc-500 font-medium mb-1 truncate">Month Profit</p>
            <p class="text-lg sm:text-2xl font-bold tracking-tight {{ $stats['month_profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} truncate">${{ number_format($stats['month_profit'], 0) }}</p>
            <p class="text-[10px] sm:text-[11px] text-zinc-400 mt-1 truncate">after COGS + expenses</p>
        </div>
    </div>

    {{-- ── 2. Production Pipeline & Revenue Chart ── --}}
    <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 min-w-0">
        
        <!-- Production Pipeline -->
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs flex flex-col justify-between min-w-0">
            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Production Pipeline</h2>
                    <a href="{{ route('orders.pipeline') }}" class="inline-flex items-center justify-center h-7 sm:h-8 px-2.5 sm:px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">View All</a>
                </div>
                
                <div class="space-y-2">
                    @php
                        $pipelineStyles = [
                            'Missing Shirt + Film' => ['bg' => 'bg-rose-50/80 hover:bg-rose-50', 'text' => 'text-rose-950', 'count' => 'text-rose-600', 'dot' => '🔴'],
                            'Missing Shirt'        => ['bg' => 'bg-amber-50/80 hover:bg-amber-50', 'text' => 'text-amber-950', 'count' => 'text-amber-600', 'dot' => '🟠'],
                            'Missing Film'         => ['bg' => 'bg-blue-50/80 hover:bg-blue-50', 'text' => 'text-blue-950', 'count' => 'text-blue-600', 'dot' => '🔵'],
                            'Ready to Print'       => ['bg' => 'bg-emerald-50/80 hover:bg-emerald-50', 'text' => 'text-emerald-950', 'count' => 'text-emerald-600', 'dot' => '🟢'],
                            'Packaging'            => ['bg' => 'bg-zinc-100/70 hover:bg-zinc-100', 'text' => 'text-zinc-800', 'count' => 'text-zinc-700', 'dot' => '📦'],
                            'Delivering'           => ['bg' => 'bg-zinc-100/70 hover:bg-zinc-100', 'text' => 'text-zinc-800', 'count' => 'text-zinc-700', 'dot' => '🚚'],
                        ];
                    @endphp

                    @foreach ($pipelineSnapshot as $row)
                        @php
                            $style = $pipelineStyles[$row['label']] ?? ['bg' => 'bg-zinc-50', 'text' => 'text-zinc-800', 'count' => 'text-zinc-600', 'dot' => '•'];
                        @endphp
                        <a href="{{ route('orders.pipeline') }}#{{ \Illuminate\Support\Str::slug($row['label']) }}"
                           class="flex items-center justify-between rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-xs font-semibold transition-colors {{ $style['bg'] }} {{ $style['text'] }}">
                            <span class="flex items-center gap-2 min-w-0 pr-2">
                                <span class="text-[10px] shrink-0">{{ $style['dot'] }}</span>
                                <span class="truncate">{{ $row['label'] }}</span>
                            </span>
                            <span class="font-bold shrink-0 {{ $style['count'] }}">{{ $row['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Revenue & Profit Responsive Chart -->
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs flex flex-col justify-between min-w-0 overflow-hidden">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Financial Performance</h2>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Last 6 Months Overview</p>
                    </div>

                    <!-- Responsive Chart Legend -->
                    <div class="flex items-center gap-3 text-xs font-medium">
                        <span class="inline-flex items-center gap-1.5 text-zinc-700 text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-zinc-900 inline-block"></span> Revenue
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-600 text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Net Profit
                        </span>
                    </div>
                </div>

                <div class="h-44 sm:h-48 w-full relative min-w-0">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 sm:gap-8 pt-3 border-t border-zinc-100 text-xs mt-3">
                <div class="flex items-center gap-2">
                    <span class="text-zinc-400">Total revenue</span>
                    <span class="font-bold text-zinc-900">${{ number_format($revenueChart['total_revenue'], 2) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-zinc-400">All-time profit</span>
                    <span class="font-bold text-emerald-600">${{ number_format($revenueChart['total_profit'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3. Best Sellers & Collections ── --}}
    <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 min-w-0">
        
        <!-- Best Sellers Table -->
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs min-w-0">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-3.5">Best Sellers</h2>
            <div class="overflow-x-auto min-w-0">
                <table class="w-full text-xs whitespace-nowrap min-w-[340px]">
                    <thead>
                        <tr class="text-left text-zinc-400 border-b border-zinc-100">
                            <th class="font-medium pb-2 w-6">#</th>
                            <th class="font-medium pb-2">Design</th>
                            <th class="font-medium pb-2">Collection</th>
                            <th class="font-medium pb-2 text-right">Sold</th>
                            <th class="font-medium pb-2 text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @forelse ($bestSellers as $i => $item)
                            <tr>
                                <td class="py-2.5 text-zinc-400">#{{ $i + 1 }}</td>
                                <td class="py-2.5 font-semibold text-zinc-800">{{ $item->design_name }}</td>
                                <td class="py-2.5 text-zinc-500">{{ $item->collection_name }}</td>
                                <td class="py-2.5 text-right font-medium text-zinc-700">{{ $item->sold }}</td>
                                <td class="py-2.5 text-right text-emerald-600 font-semibold">${{ number_format($item->profit, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-zinc-400">No sales yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collections Summary Table -->
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs min-w-0">
            <div class="flex items-center justify-between mb-3.5">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Collections</h2>
                <a href="{{ route('collections.index') }}" class="inline-flex items-center justify-center h-7 sm:h-8 px-2.5 sm:px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">Manage</a>
            </div>
            <div class="overflow-x-auto min-w-0">
                <table class="w-full text-xs whitespace-nowrap min-w-[320px]">
                    <thead>
                        <tr class="text-left text-zinc-400 border-b border-zinc-100">
                            <th class="font-medium pb-2">Collection</th>
                            <th class="font-medium pb-2 text-right">Orders</th>
                            <th class="font-medium pb-2 text-right">Revenue</th>
                            <th class="font-medium pb-2 text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @foreach ($collectionsSummary as $c)
                            <tr>
                                <td class="py-2.5 font-semibold text-zinc-800">{{ $c->name }}</td>
                                <td class="py-2.5 text-right font-medium text-zinc-700">{{ $c->orders_count }}</td>
                                <td class="py-2.5 text-right font-medium text-zinc-700">${{ number_format($c->revenue, 2) }}</td>
                                <td class="py-2.5 text-right text-emerald-600 font-semibold">${{ number_format($c->profit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 4. Inventory Alerts ── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs min-w-0">
        <div class="flex items-center justify-between mb-3.5">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider flex items-center gap-1.5 sm:gap-2">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Inventory Alerts</span>
                <span class="text-xs font-normal text-zinc-400">({{ $inventoryAlerts->count() }})</span>
            </h2>
            <a href="{{ route('inventory.shirts') }}?filter=low_stock" class="inline-flex items-center justify-center h-7 sm:h-8 px-2.5 sm:px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">View All</a>
        </div>
        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-0.5 text-xs">
            @forelse ($inventoryAlerts->take(6) as $alert)
                <div class="flex items-center justify-between py-2 border-b border-zinc-100 min-w-0">
                    <span class="text-zinc-700 font-medium truncate pr-2">{{ $alert['label'] }}</span>
                    <span class="font-bold text-rose-600 shrink-0">{{ $alert['quantity'] }}</span>
                </div>
            @empty
                <p class="text-zinc-400 col-span-2 py-2">Nothing low on stock right now.</p>
            @endforelse
        </div>
    </div>

    {{-- ── 5. Recent Orders ── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs min-w-0">
        <div class="flex items-center justify-between mb-3.5">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Recent Orders</h2>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center h-7 sm:h-8 px-2.5 sm:px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs transition-all">View All</a>
        </div>

        <!-- Mobile View Card List (< sm) -->
        <div class="block sm:hidden divide-y divide-zinc-100">
            @foreach ($recentOrders as $order)
                <a href="{{ route('orders.show', $order) }}" class="block py-3 space-y-2 first:pt-0 last:pb-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-zinc-900 text-xs">{{ $order->customer_handle }}</span>
                        <span class="font-bold text-zinc-900 text-xs">${{ number_format($order->total_price, 2) }}</span>
                    </div>
                    <div class="text-[11px] text-zinc-500 truncate">
                        {{ $order->design->name }} — {{ $order->size }}/{{ $order->color }}
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                        <span class="badge text-[10px] {{ $order->getReadinessBadge()['class'] }}">{{ $order->getReadinessBadge()['label'] }}</span>
                        <span class="badge text-[10px] {{ $order->getDeliveryBadgeClass() }}">{{ $order->delivery_status }}</span>
                        <span class="text-[10px] text-zinc-400 ml-auto">{{ $order->order_date->format('d M') }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Desktop View Table (>= sm) -->
        <div class="hidden sm:block overflow-x-auto min-w-0">
            <table class="w-full text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-left text-zinc-400 border-b border-zinc-100">
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
                <tbody class="divide-y divide-zinc-50">
                    @foreach ($recentOrders as $order)
                        <tr class="hover:bg-zinc-50/80 cursor-pointer transition-colors" onclick="window.location='{{ route('orders.show', $order) }}'">
                            <td class="py-2.5 text-zinc-500">{{ $order->order_date->format('d M') }}</td>
                            <td class="py-2.5 font-semibold text-zinc-800">{{ $order->customer_handle }}</td>
                            <td class="py-2.5 text-zinc-600">{{ $order->design->name }} — {{ $order->design->collection->name }}</td>
                            <td class="py-2.5 text-zinc-500">{{ $order->size }} / {{ $order->color }}</td>
                            <td class="py-2.5"><span class="badge {{ $order->getReadinessBadge()['class'] }}">{{ $order->getReadinessBadge()['label'] }}</span></td>
                            <td class="py-2.5"><span class="badge {{ $order->getDeliveryBadgeClass() }}">{{ $order->delivery_status }}</span></td>
                            <td class="py-2.5"><span class="badge {{ $order->getPaymentBadgeClass() }}">{{ $order->payment_status }}</span></td>
                            <td class="py-2.5 text-right font-semibold text-zinc-900">${{ number_format($order->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 180);
        revenueGradient.addColorStop(0, 'rgba(24, 24, 27, 0.15)');
        revenueGradient.addColorStop(1, 'rgba(24, 24, 27, 0.0)');

        const profitGradient = ctx.createLinearGradient(0, 0, 0, 180);
        profitGradient.addColorStop(0, 'rgba(16, 185, 129, 0.20)');
        profitGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($revenueChart['labels']),
                datasets: [
                    {
                        label: 'Revenue',
                        data: @json($revenueChart['revenue']),
                        borderColor: '#18181b',
                        borderWidth: 2,
                        backgroundColor: revenueGradient,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointBackgroundColor: '#18181b',
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Net Profit',
                        data: @json($revenueChart['profit']),
                        borderColor: '#10b981',
                        borderWidth: 2,
                        backgroundColor: profitGradient,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointBackgroundColor: '#10b981',
                        pointHoverRadius: 5,
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#18181b',
                        titleColor: '#f4f4f5',
                        bodyColor: '#f4f4f5',
                        padding: 8,
                        cornerRadius: 6,
                        displayColors: true,
                        boxWidth: 6,
                        boxHeight: 6,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f4f4f6',
                            drawBorder: false,
                        },
                        ticks: {
                            font: { size: 9, family: 'Inter, sans-serif' },
                            color: '#a1a1aa',
                            maxTicksLimit: 4,
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        },
                        border: { dash: [4, 4], display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 9, family: 'Inter, sans-serif' },
                            color: '#a1a1aa'
                        },
                        border: { display: false }
                    },
                },
            },
        });
    });
</script>
@endpush