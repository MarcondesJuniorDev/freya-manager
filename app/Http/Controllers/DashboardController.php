<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // 1. Calculations for stock
        $stockCost = (float) Product::sum(DB::raw('cost_price * stock_quantity'));
        $stockValue = (float) Product::sum(DB::raw('catalog_price * stock_quantity'));
        $potentialProfit = $stockValue - $stockCost;

        // 2. Outstanding debt (caderneta)
        $fiadoReceivables = (float) Customer::where('balance', '>', 0)->sum('balance');

        // 3. Sales statistics
        $salesToday = (float) Sale::whereDate('sale_date', today())->sum('total_amount');
        $salesThisMonth = (float) Sale::whereMonth('sale_date', now()->month)->sum('total_amount');

        // 4. Low stock alert
        $lowStockProducts = Product::where('stock_quantity', '<=', DB::raw('min_stock_quantity'))
            ->with('brand')
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        // 5. Recent Sales
        $recentSales = Sale::with('customer')
            ->orderBy('sale_date', 'desc')
            ->take(5)
            ->get();

        // 6. Chart data (last 7 days of sales)
        $chartData = DB::table('sales')
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->where('sale_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $timestamp = strtotime((string) $item->date);
                return [
                    'date' => $timestamp !== false ? date('d/m', $timestamp) : '',
                    'total' => (float) $item->total,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'stockCost' => round($stockCost, 2),
                'stockValue' => round($stockValue, 2),
                'potentialProfit' => round($potentialProfit, 2),
                'fiadoReceivables' => round($fiadoReceivables, 2),
                'salesToday' => round($salesToday, 2),
                'salesThisMonth' => round($salesThisMonth, 2),
            ],
            'lowStockProducts' => $lowStockProducts,
            'recentSales' => $recentSales,
            'chartData' => $chartData,
        ]);
    }
}
