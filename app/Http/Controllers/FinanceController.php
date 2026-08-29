<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $selectedMonth = $request->filled('month') ? (int) $request->input('month') : null;

        // Startup Capital constant
        $startupCapital = 100.00;

        // 1. Calculate financial data for each month (1 to 12)
        $allMonthlyData = collect(range(1, 12))->map(function ($m) use ($year) {
            $orders = Order::with('shirtType')->whereYear('order_date', $year)->whereMonth('order_date', $m)->get();
            $expenses = Expense::whereYear('expense_date', $year)->whereMonth('expense_date', $m)->sum('amount');

            $revenue = $orders->sum(fn ($o) => $o->recognized_revenue);
            $cogs = $orders->sum(fn ($o) => $o->recognized_cogs);
            $grossProfit = $revenue - $cogs;
            $netProfit = $grossProfit - $expenses;

            $validOrdersCount = $orders->filter(fn ($o) => $o->recognized_revenue > 0 || $o->recognized_cogs > 0)->count();

            return [
                'month_num' => $m,
                'month' => Carbon::create($year, $m, 1)->format('F'),
                'orders_count' => $validOrdersCount,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'expenses' => $expenses,
                'net_profit' => $netProfit,
            ];
        });

        // 2. Filter Monthly Breakdown Table based on selection
        $monthlyData = $allMonthlyData->filter(function ($row) use ($selectedMonth) {
            if ($selectedMonth) {
                return $row['month_num'] === $selectedMonth;
            }
            return $row['orders_count'] > 0 || $row['expenses'] > 0;
        })->values();

        // 3. Calculate Top Stat Cards based on Selected Month OR Full Year
        $summaryData = $selectedMonth
            ? $allMonthlyData->where('month_num', $selectedMonth)
            : $allMonthlyData;

        $annualRevenue = $summaryData->sum('revenue');
        $costOfGoods = $summaryData->sum('cogs');
        $expensesTotal = $summaryData->sum('expenses');
        $netProfit = $summaryData->sum('net_profit');
        $profitMargin = $annualRevenue > 0 ? ($netProfit / $annualRevenue) * 100 : 0;

        // Total Cash Available = Startup Money ($100) + Accumulated Net Profit
        $cashBalance = $startupCapital + $netProfit;

        // 4. Expenses Query
        $expensesQuery = Expense::whereYear('expense_date', $year);
        if ($selectedMonth) {
            $expensesQuery->whereMonth('expense_date', $selectedMonth);
        }

        $expensesByCategory = (clone $expensesQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')->orderByDesc('total')->get();

        $expenseLog = (clone $expensesQuery)->orderByDesc('expense_date')->get();

        // 5. Top Selling Designs
        $topDesignsQuery = Order::with('design')
            ->whereYear('order_date', $year)
            ->whereIn('payment_status', ['Paid', 'Partial']);

        if ($selectedMonth) {
            $topDesignsQuery->whereMonth('order_date', $selectedMonth);
        }

        $topSellingDesigns = $topDesignsQuery
            ->select('design_id', DB::raw('COUNT(*) as total_sold'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('design_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 6. Revenue by Source
        $revenueBySourceQuery = Order::whereYear('order_date', $year)->whereIn('payment_status', ['Paid', 'Partial']);
        if ($selectedMonth) {
            $revenueBySourceQuery->whereMonth('order_date', $selectedMonth);
        }
        $revenueBySource = $revenueBySourceQuery
            ->select('source', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('source')->orderByDesc('revenue')->get();

        // 7. Top Customers
        $topCustomersQuery = Order::whereYear('order_date', $year)->whereIn('payment_status', ['Paid', 'Partial']);
        if ($selectedMonth) {
            $topCustomersQuery->whereMonth('order_date', $selectedMonth);
        }
        $topCustomers = $topCustomersQuery
            ->select('customer_handle', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total_price) as total_spent'))
            ->groupBy('customer_handle')->orderByDesc('total_spent')->take(10)->get();

        // 8. Unpaid Orders
        $unpaidOrders = Order::with('design.collection')
            ->where('delivery_status', '!=', 'Cancelled')
            ->where('payment_status', '!=', 'Paid')
            ->orderByDesc('order_date')->get();
        $unpaidTotal = $unpaidOrders->sum(fn ($o) => $o->total_price - $o->partial_amount);

        $years = Order::selectRaw('DISTINCT YEAR(order_date) as y')->orderByDesc('y')->pluck('y');
        if ($years->isEmpty()) $years = collect([now()->year]);

        return view('finance.index', compact(
            'year', 'selectedMonth', 'years', 'monthlyData', 'annualRevenue', 'costOfGoods', 'expensesTotal', 
            'netProfit', 'profitMargin', 'startupCapital', 'cashBalance',
            'expensesByCategory', 'expenseLog', 'topSellingDesigns', 'revenueBySource', 'topCustomers', 'unpaidOrders', 'unpaidTotal'
        ));
    }

    public function addExpense(Request $request)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|in:' . implode(',', Expense::categories()),
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:100',
        ]);

        Expense::create($data);

        return back()->with('success', "Expense \"{$data['title']}\" added!");
    }

    public function deleteExpense(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense removed.');
    }
}