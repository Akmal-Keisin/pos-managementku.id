<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics and insights.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Overview Statistics
        $totalRevenue = Transaction::sum('total');
        $todayRevenue = Transaction::whereDate('created_at', today())->sum('total');
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::whereDate('created_at', today())->count();
        $totalProducts = Product::count();
        $lowStockProducts = Product::where('current_stock', '<', 10)->count();

        // Revenue Trends (Last 7 days)
        $revenueTrends = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('COUNT(*) as transactions')
        )
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Top Selling Products
        $topProducts = Product::select('id', 'name', 'sku', 'total_sold', 'current_stock', 'price')
            ->where('total_sold', '>', 0)
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        // Low Stock Products
        $lowStockItems = Product::select('id', 'name', 'sku', 'current_stock', 'price')
            ->where('current_stock', '<', 10)
            ->orderBy('current_stock', 'asc')
            ->limit(5)
            ->get();

        // Recent Transactions
        $recentTransactions = Transaction::with(['user', 'details'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => [
                        'name' => $transaction->user->name,
                        'username' => $transaction->user->username,
                    ],
                    'total' => $transaction->total,
                    'items_count' => $transaction->details->sum('quantity'),
                    'created_at' => $transaction->created_at,
                ];
            });

        // Recent Stock Movements
        $recentStockMovements = StockHistory::with(['product', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'product' => [
                        'name' => $history->product->name,
                        'sku' => $history->product->sku,
                    ],
                    'user' => [
                        'name' => $history->user->name,
                    ],
                    'type' => $history->type,
                    'quantity' => $history->quantity,
                    'created_at' => $history->created_at,
                ];
            });

        // User Activity (for admins)
        $userActivity = null;
        if ($user && in_array($user->role, ['super-admin', 'admin'])) {
            $userActivity = User::withCount(['stockHistories'])
                ->select('id', 'name', 'username', 'role')
                ->where('role', '!=', 'super-admin')
                ->limit(5)
                ->get();
        }

        // Sales by User (Today)
        $salesByUser = Transaction::select('user_id', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(total) as total_sales'))
            ->whereDate('created_at', today())
            ->with('user:id,name,username')
            ->groupBy('user_id')
            ->orderBy('total_sales', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'user' => [
                        'name' => $item->user->name,
                        'username' => $item->user->username,
                    ],
                    'transaction_count' => $item->transaction_count,
                    'total_sales' => $item->total_sales,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'total_transactions' => $totalTransactions,
                'today_transactions' => $todayTransactions,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
            ],
            'revenue_trends' => $revenueTrends,
            'top_products' => $topProducts,
            'low_stock_items' => $lowStockItems,
            'recent_transactions' => $recentTransactions,
            'recent_stock_movements' => $recentStockMovements,
            'sales_by_user' => $salesByUser,
            'user_activity' => $userActivity,
        ]);
    }
}
