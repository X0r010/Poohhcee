@extends('layouts.app')

@section('title', 'Finance')

@section('header-actions')
    <form method="GET" class="flex items-center gap-2">
        @if(request('month'))
            <input type="hidden" name="month" value="{{ request('month') }}">
        @endif
        <select name="year" onchange="this.form.submit()" class="h-9 w-full sm:w-auto rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 focus:outline-none focus:border-zinc-900">
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ categoryFilter: '', showDeleteModal: false, deleteActionUrl: '', deleteExpenseTitle: '' }">

    {{-- Month Filter Bar --}}
    <div class="flex items-center justify-between bg-white border border-zinc-200/80 rounded-xl p-3 shadow-2xs">
        <span class="text-xs font-semibold text-zinc-700">Filter Analytics Period:</span>
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('finance.index', ['year' => $year]) }}" 
               class="px-2.5 py-1 rounded-md text-xs font-medium {{ !$selectedMonth ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
               All Year ({{ $year }})
            </a>
            @foreach (range(1, 12) as $m)
                @php $mName = \Carbon\Carbon::create($year, $m, 1)->format('M'); @endphp
                <a href="{{ route('finance.index', ['year' => $year, 'month' => $m]) }}" 
                   class="px-2.5 py-1 rounded-md text-xs font-medium {{ $selectedMonth == $m ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                   {{ $mName }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── Stat cards ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="bg-white border border-zinc-200/80 rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-zinc-500 mb-1">Starting Capital</p>
            <p class="text-xl sm:text-2xl font-bold text-zinc-900">${{ number_format($startupCapital, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-zinc-400 mt-1">Initial investment</p>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-zinc-500 mb-1">Revenue</p>
            <p class="text-xl sm:text-2xl font-bold text-zinc-900">${{ number_format($annualRevenue, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-zinc-400 mt-1">{{ $selectedMonth ? \Carbon\Carbon::create($year, $selectedMonth, 1)->format('F Y') : $year }}</p>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-zinc-500 mb-1">COGS</p>
            <p class="text-xl sm:text-2xl font-bold text-zinc-900">${{ number_format($costOfGoods, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-zinc-400 mt-1">shirts + film costs</p>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-zinc-500 mb-1">Expenses</p>
            <p class="text-xl sm:text-2xl font-bold text-zinc-900">${{ number_format($expensesTotal, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-zinc-400 mt-1">operating costs</p>
        </div>
        <div class="bg-zinc-900 text-white rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-zinc-400 mb-1">Net Profit</p>
            <p class="text-xl sm:text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">${{ number_format($netProfit, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-zinc-500 mt-1">Margin: {{ number_format($profitMargin, 1) }}%</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl p-3.5 sm:p-5 shadow-2xs">
            <p class="text-[11px] sm:text-xs text-emerald-800 font-medium mb-1">Total Cash Balance</p>
            <p class="text-xl sm:text-2xl font-bold text-emerald-900">${{ number_format($cashBalance, 2) }}</p>
            <p class="text-[10px] sm:text-xs text-emerald-600 mt-1">Capital + Net Profit</p>
        </div>
    </div>

    {{-- ── Monthly P&L Table ──────────────────────────────────────── --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Monthly Breakdown — {{ $year }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left sm:whitespace-nowrap">
                <thead class="hidden sm:table-header-group">
                    <tr class="text-zinc-400 border-b border-zinc-100">
                        <th class="font-medium pb-2.5">Month</th>
                        <th class="font-medium pb-2.5 text-right">Orders</th>
                        <th class="font-medium pb-2.5 text-right">Revenue</th>
                        <th class="font-medium pb-2.5 text-right">COGS</th>
                        <th class="font-medium pb-2.5 text-right">Gross Profit</th>
                        <th class="font-medium pb-2.5 text-right">Expenses</th>
                        <th class="font-medium pb-2.5 text-right">Net Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 sm:divide-zinc-50">
                    @forelse ($monthlyData as $row)
                        <tr class="block sm:table-row p-3.5 mb-3 sm:mb-0 rounded-xl border border-zinc-200/80 sm:border-0 {{ $selectedMonth == $row['month_num'] ? 'bg-zinc-100/80' : 'bg-white hover:bg-zinc-50/50' }} transition-colors shadow-2xs sm:shadow-none space-y-2 sm:space-y-0">
                            
                            <td class="block sm:table-cell sm:py-2.5 font-bold sm:font-medium text-zinc-800">
                                <div class="flex items-center justify-between sm:block">
                                    <a href="{{ route('finance.index', ['year' => $year, 'month' => $row['month_num']]) }}" class="hover:underline text-zinc-900 font-semibold">
                                        {{ $row['month'] }}
                                    </a>
                                    <span class="sm:hidden text-[11px] font-normal text-zinc-500 bg-zinc-100 px-2 py-0.5 rounded">
                                        {{ $row['orders_count'] }} orders
                                    </span>
                                </div>
                            </td>

                            <td class="hidden sm:table-cell sm:py-2.5 text-right">{{ $row['orders_count'] }}</td>
                            
                            <td class="block sm:table-cell sm:py-2.5 text-left sm:text-right">
                                <div class="grid grid-cols-2 gap-2 sm:block text-[11px] sm:text-xs">
                                    <div class="sm:hidden text-zinc-400">Revenue:</div>
                                    <div class="font-semibold text-zinc-900">${{ number_format($row['revenue'], 2) }}</div>
                                </div>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-left sm:text-right">
                                <div class="grid grid-cols-2 gap-2 sm:block text-[11px] sm:text-xs">
                                    <div class="sm:hidden text-zinc-400">COGS:</div>
                                    <div class="text-rose-500">-${{ number_format($row['cogs'], 2) }}</div>
                                </div>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-left sm:text-right">
                                <div class="grid grid-cols-2 gap-2 sm:block text-[11px] sm:text-xs">
                                    <div class="sm:hidden text-zinc-400">Gross Profit:</div>
                                    <div class="text-zinc-800 font-medium">${{ number_format($row['gross_profit'], 2) }}</div>
                                </div>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-left sm:text-right">
                                <div class="grid grid-cols-2 gap-2 sm:block text-[11px] sm:text-xs">
                                    <div class="sm:hidden text-zinc-400">Expenses:</div>
                                    <div class="text-rose-500">-${{ number_format($row['expenses'], 2) }}</div>
                                </div>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 pt-2 sm:pt-2.5 border-t border-zinc-100 sm:border-t-0 text-left sm:text-right">
                                <div class="flex items-center justify-between sm:block text-[11px] sm:text-xs">
                                    <span class="sm:hidden font-bold text-zinc-700">Net Profit:</span>
                                    <span class="font-bold text-xs sm:text-xs {{ $row['net_profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        ${{ number_format($row['net_profit'], 2) }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-zinc-400 text-xs">No activity in {{ $year }} yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top Selling Designs & Revenue by Source --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        {{-- Best Selling Designs --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Top Selling Designs {{ $selectedMonth ? '('. \Carbon\Carbon::create($year, $selectedMonth, 1)->format('M Y') .')' : "($year)" }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-zinc-400 border-b border-zinc-100">
                            <th class="font-medium pb-2.5">Design</th>
                            <th class="font-medium pb-2.5 text-right">Units Sold</th>
                            <th class="font-medium pb-2.5 text-right">Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @forelse ($topSellingDesigns as $item)
                            <tr>
                                <td class="py-2.5 font-medium text-zinc-800">{{ $item->design->name ?? 'Unknown Design' }}</td>
                                <td class="py-2.5 text-right text-zinc-600">{{ $item->total_sold }}</td>
                                <td class="py-2.5 text-right font-semibold text-zinc-900">${{ number_format($item->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-zinc-400">No sales reported for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Revenue by Source --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Revenue by Source</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-zinc-400 border-b border-zinc-100">
                            <th class="font-medium pb-2.5">Source</th>
                            <th class="font-medium pb-2.5 text-right">Orders</th>
                            <th class="font-medium pb-2.5 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @forelse ($revenueBySource as $s)
                            <tr>
                                <td class="py-2.5 font-medium text-zinc-800">{{ $s->source }}</td>
                                <td class="py-2.5 text-right text-zinc-600">{{ $s->orders_count }}</td>
                                <td class="py-2.5 text-right font-semibold text-zinc-900">${{ number_format($s->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-zinc-400">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Expense Management --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        {{-- Add Expense --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Add Expense</h2>
            <form method="POST" action="{{ route('finance.expenses.add') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Date</label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Category</label>
                        <select name="category" required class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                            @foreach (\App\Models\Expense::categories() as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Title</label>
                    <input type="text" name="title" required placeholder="e.g. Lunch / Stickers / Films"
                           class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                               class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="Receipt #..."
                               class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional notes..."
                              class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 bg-white"></textarea>
                </div>
                <button type="submit" class="w-full sm:w-auto h-10 sm:h-9 px-5 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-2xs transition-colors">Add Expense</button>
            </form>
        </div>

        {{-- Expenses by Category --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Expenses by Category</h2>
            <div class="space-y-2">
                @forelse ($expensesByCategory as $cat)
                    <button type="button" @click="categoryFilter = categoryFilter === '{{ $cat->category }}' ? '' : '{{ $cat->category }}'"
                            class="w-full flex items-center justify-between rounded-lg px-3.5 py-2.5 text-xs transition active:scale-[0.99]"
                            :class="categoryFilter === '{{ $cat->category }}' ? 'bg-zinc-900 text-white' : 'bg-zinc-50 text-zinc-700 hover:bg-zinc-100 border border-zinc-200/60'">
                        <span class="font-medium">{{ $cat->category }}</span>
                        <span class="font-semibold">${{ number_format($cat->total, 2) }}</span>
                    </button>
                @empty
                    <p class="text-xs text-zinc-400">No expenses recorded for this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Expense Log --}}
    <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Expense Log</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left sm:whitespace-nowrap">
                <thead class="hidden sm:table-header-group">
                    <tr class="text-zinc-400 border-b border-zinc-100">
                        <th class="font-medium pb-2.5">Date</th>
                        <th class="font-medium pb-2.5">Category</th>
                        <th class="font-medium pb-2.5">Title</th>
                        <th class="font-medium pb-2.5 text-right">Amount</th>
                        <th class="font-medium pb-2.5">Reference</th>
                        <th class="font-medium pb-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 sm:divide-zinc-50">
                    @forelse ($expenseLog as $expense)
                        <tr x-show="categoryFilter === '' || categoryFilter === '{{ $expense->category }}'" 
                            class="block sm:table-row p-3.5 mb-3 sm:mb-0 rounded-xl border border-zinc-200/80 sm:border-0 bg-white hover:bg-zinc-50/50 transition-colors shadow-2xs sm:shadow-none space-y-2 sm:space-y-0">
                            
                            <td class="block sm:table-cell sm:py-2.5 text-zinc-500">
                                <div class="flex items-center justify-between sm:block">
                                    <span>{{ $expense->expense_date->format('d M Y') }}</span>
                                    <span class="sm:hidden font-bold text-rose-600 text-xs">
                                        -${{ number_format($expense->amount, 2) }}
                                    </span>
                                </div>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5">
                                <span class="badge badge-unknown">{{ $expense->category }}</span>
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 font-medium text-zinc-800">
                                {{ $expense->title }}
                            </td>

                            <td class="hidden sm:table-cell sm:py-2.5 text-right font-semibold text-rose-600">
                                -${{ number_format($expense->amount, 2) }}
                            </td>

                            <td class="block sm:table-cell sm:py-2.5 text-zinc-500">
                                <span class="sm:hidden text-zinc-400 mr-1">Ref:</span>{{ $expense->reference ?: '—' }}
                            </td>

                            <td class="block sm:table-cell pt-2 sm:pt-2.5 border-t border-zinc-100 sm:border-t-0 text-left sm:text-right">
                                <button type="button" 
                                        @click="showDeleteModal = true; deleteActionUrl = '{{ route('finance.expenses.delete', $expense) }}'; deleteExpenseTitle = '{{ addslashes($expense->title) }}'" 
                                        class="text-rose-500 hover:text-rose-700 font-medium cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-zinc-400 text-xs">No expenses recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Custom Confirmation Modal --}}
    <div x-show="showDeleteModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/40 p-4 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="showDeleteModal = false" 
             class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl border border-zinc-200/80 space-y-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            
            <div class="space-y-1.5">
                <h3 class="text-sm font-bold text-zinc-900">Delete Expense</h3>
                <p class="text-xs text-zinc-500">
                    Are you sure you want to delete <span class="font-semibold text-zinc-900" x-text="deleteExpenseTitle"></span>? This action cannot be undone.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" 
                        @click="showDeleteModal = false" 
                        class="h-8 px-3 rounded-lg border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 transition-colors cursor-pointer">
                    Cancel
                </button>

                <form :action="deleteActionUrl" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="h-8 px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors cursor-pointer">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection