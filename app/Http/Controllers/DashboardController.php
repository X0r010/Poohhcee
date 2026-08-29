<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\FilmInventory;
use App\Models\Order;
use App\Models\ShirtInventory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 2;

    public function index()
    {
        $stats = $this->buildStats();
        $pipelineSnapshot = $this->buildPipelineSnapshot();
        $revenueChart = $this->buildRevenueChart();
        $bestSellers = $this->buildBestSellers();
        $collectionsSummary = $this->buildCollectionsSummary();
        $inventoryAlerts = $this->buildInventoryAlerts();
        $recentOrders = Order::with('design.collection')->orderByDesc('order_date')->orderByDesc('id')->take(8)->get();

        return view('layouts.dashboard', compact(
            'stats', 
            'pipelineSnapshot', 
            'revenueChart', 
            'bestSellers', 
            'collectionsSummary', 
            'inventoryAlerts', 
            'recentOrders'
        ));
    }

    private function buildStats(): array
    {
        $monthOrders = Order::whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month)
            ->get();

        $monthExpenses = Expense::whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        $monthRevenue = $monthOrders->sum(fn ($o) => $o->recognized_revenue);
        $monthCogs    = $monthOrders->sum(fn ($o) => $o->recognized_cogs);
        $monthProfit  = ($monthRevenue - $monthCogs) - $monthExpenses;

        $unpaid = Order::whereIn('payment_status', ['Not Yet', 'Partial'])
            ->where('delivery_status', '!=', 'Cancelled')
            ->get();

        return [
            'total_orders'      => Order::count(),
            'orders_this_month' => $monthOrders->count(),
            'pending'           => Order::whereNotIn('delivery_status', ['Delivered', 'Cancelled'])->count(),
            'ready_to_print'    => Order::where('readiness', 'ready')->where('print_status', 'Pending')->count(),
            'month_revenue'     => $monthRevenue,
            'month_expenses'    => $monthExpenses,
            'month_profit'      => $monthProfit,
            'unpaid_count'      => $unpaid->count(),
            'unpaid_total'      => $unpaid->sum(fn ($o) => $o->total_price - $o->partial_amount),
        ];
    }

    private function buildPipelineSnapshot(): array
    {
        $counts = Order::whereNotIn('delivery_status', ['Delivered', 'Cancelled'])
            ->select('readiness', 'shirt_status', 'film_status', 'print_status', 'delivery_status')
            ->get();

        return [
            ['label' => 'Missing Shirt + Film', 'bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#ef4444', 'count' => $counts->where('readiness', 'missing_both')->count()],
            ['label' => 'Missing Shirt',        'bg' => '#fffbeb', 'text' => '#b45309', 'dot' => '#f59e0b', 'count' => $counts->where('readiness', 'missing_shirt')->count()],
            ['label' => 'Missing Film',         'bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#3b82f6', 'count' => $counts->where('readiness', 'missing_film')->count()],
            ['label' => 'Ready to Print',       'bg' => '#ecfdf5', 'text' => '#047857', 'dot' => '#10b981', 'count' => $counts->where('readiness', 'ready')->count()],
            ['label' => 'Packaging',            'bg' => '#fffbeb', 'text' => '#b45309', 'dot' => '#f59e0b', 'count' => $counts->where('delivery_status', 'Packaging')->count()],
            ['label' => 'Delivering',           'bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#3b82f6', 'count' => $counts->where('delivery_status', 'Delivering')->count()],
        ];
    }

    /**
     * Enhanced Chart Payload:
     * Aligns 100% with FinanceController accounting logic (recognized_revenue - recognized_cogs - expenses).
     * Provides comprehensive datasets for revenue, COGS, expenses, gross profit, and net profit.
     */
    private function buildRevenueChart(): array
    {
        // Chronological order for the last 6 months
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $rows = $months->map(function ($month) {
            $orders = Order::whereYear('order_date', $month->year)
                ->whereMonth('order_date', $month->month)
                ->get();

            $expenses = Expense::whereYear('expense_date', $month->year)
                ->whereMonth('expense_date', $month->month)
                ->sum('amount');

            $revenue     = $orders->sum(fn ($o) => $o->recognized_revenue);
            $cogs        = $orders->sum(fn ($o) => $o->recognized_cogs);
            $grossProfit = $revenue - $cogs;
            $netProfit   = $grossProfit - $expenses;

            return [
                'label'        => $month->format('M'),
                'full_label'   => $month->format('F Y'),
                'revenue'      => round($revenue, 2),
                'cogs'         => round($cogs, 2),
                'gross_profit' => round($grossProfit, 2),
                'expenses'     => round($expenses, 2),
                'profit'       => round($netProfit, 2),
            ];
        });

        // All-Time accounting matching Finance Controller rules
        $allOrders     = Order::get();
        $totalRevenue  = $allOrders->sum(fn ($o) => $o->recognized_revenue);
        $totalCogs     = $allOrders->sum(fn ($o) => $o->recognized_cogs);
        $totalExpenses = Expense::sum('amount');
        $totalProfit   = ($totalRevenue - $totalCogs) - $totalExpenses;

        return [
            'labels'        => $rows->pluck('label'),
            'full_labels'   => $rows->pluck('full_label'),
            'revenue'       => $rows->pluck('revenue'),
            'cogs'          => $rows->pluck('cogs'),
            'expenses'      => $rows->pluck('expenses'),
            'gross_profit'  => $rows->pluck('gross_profit'),
            'profit'        => $rows->pluck('profit'),
            'total_revenue' => round($totalRevenue, 2),
            'total_profit'  => round($totalProfit, 2),
        ];
    }

    private function buildBestSellers()
    {
        return Order::with('design.collection')
            ->where('delivery_status', '!=', 'Cancelled')
            ->get()
            ->groupBy('design_id')
            ->map(function ($orders) {
                $first = $orders->first();
                $revenue = $orders->sum(fn ($o) => $o->recognized_revenue);
                $cogs    = $orders->sum(fn ($o) => $o->recognized_cogs);

                return (object) [
                    'design_name'     => $first->design->name ?? 'Unknown Design',
                    'collection_name' => $first->design->collection->name ?? 'Unknown Collection',
                    'sold'            => $orders->count(),
                    'profit'          => $revenue - $cogs,
                ];
            })
            ->sortByDesc('sold')
            ->take(5)
            ->values();
    }

    private function buildCollectionsSummary()
    {
        return Collection::withCount(['orders' => fn ($q) => $q->where('delivery_status', '!=', 'Cancelled')])
            ->get()
            ->map(function ($collection) {
                $orders = Order::whereHas('design', fn ($q) => $q->where('collection_id', $collection->id))
                    ->where('delivery_status', '!=', 'Cancelled')
                    ->get();

                $revenue = $orders->sum(fn ($o) => $o->recognized_revenue);
                $cogs    = $orders->sum(fn ($o) => $o->recognized_cogs);

                $collection->revenue = $revenue;
                $collection->profit  = $revenue - $cogs;
                return $collection;
            })
            ->sortByDesc('revenue')
            ->values();
    }

    private function buildInventoryAlerts()
    {
        $shirts = ShirtInventory::where('quantity', '<=', self::LOW_STOCK_THRESHOLD)
            ->get()
            ->map(fn ($s) => ['label' => "{$s->type} — {$s->size} / {$s->color}", 'quantity' => $s->quantity]);

        $films = FilmInventory::with('design.collection')
            ->where('prints_available', '<=', self::LOW_STOCK_THRESHOLD)
            ->get()
            ->map(fn ($f) => [
                'label' => "{$f->design->collection->name} — {$f->design->name} (" . ucfirst($f->side) . ')',
                'quantity' => $f->prints_available,
            ]);

        return $shirts->concat($films)->sortBy('quantity')->values();
    }
}