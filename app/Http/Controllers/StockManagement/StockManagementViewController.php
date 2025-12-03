<?php

namespace App\Http\Controllers\StockManagement;

use App\Http\Controllers\Controller;
use App\Models\StockHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockManagementViewController extends Controller
{
    /**
     * Display a listing of stock histories.
     */
    public function index(Request $request): Response
    {
        $query = StockHistory::with(['product', 'user'])->latest();

        // Date range filter
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Apply other filters
        if ($request->has('product_id') && $request->product_id && $request->product_id != 'All Products') {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('user_id') && $request->user_id && $request->user_id != 'All Users') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type') && $request->type && $request->type != 'All Types') {
            $query->where('type', $request->type);
        }

        $stockHistories = $query->paginate(10);

        // Get filter options
        $products = Product::select('id', 'name', 'sku')->get();
        $users = User::select('id', 'name', 'username')
            ->whereIn('role', ['super-admin', 'admin'])
            ->get();

        return Inertia::render('stock-management/Index', [
            'stockHistories' => $stockHistories,
            'products' => $products,
            'users' => $users,
            'filters' => $request->only(['start_date', 'end_date', 'product_id', 'user_id', 'type']),
        ]);
    }

    /**
     * Show the form for updating stock.
     */
    public function updateStock(): Response
    {
        $products = Product::select('id', 'name', 'sku', 'current_stock')->get();

        return Inertia::render('stock-management/UpdateStock', [
            'products' => $products,
        ]);
    }
}
