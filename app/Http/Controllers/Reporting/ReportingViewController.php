<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportingViewController extends Controller
{
    /**
     * Display a listing of transactions with filters.
     */
    public function index(Request $request): Response
    {
        $query = Transaction::with(['user', 'details.product']);

        // Date filter - defaults to today
        $date = $request->get('date', now()->format('Y-m-d'));
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // User filter - defaults to current user
        $userId = $request->get('user_id', $request->user()?->id);
        if ($userId && $userId !== 'All User') {
            $query->where('user_id', $userId);
        }

        // Product filter
        if ($request->has('product_id') && $request->product_id && $request->product_id !== 'All Product') {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })->orWhereHas('details.product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });
        }

        $transactions = $query->latest()->paginate(10);

        // Get all users for filter dropdown
        $users = User::select('id', 'name', 'username')->get();

        // Get all products for filter dropdown
        $products = Product::select('id', 'name', 'sku')->get();

        return Inertia::render('reporting/Index', [
            'transactions' => $transactions,
            'users' => $users,
            'products' => $products,
            'filters' => [
                'search' => $request->search,
                'date' => $date,
                'user_id' => $userId,
                'product_id' => $request->product_id,
            ],
        ]);
    }

    /**
     * Show the details of a specific transaction.
     */
    public function show(Transaction $transaction): Response
    {
        $transaction->load(['user', 'details.product']);

        return Inertia::render('reporting/Detail', [
            'transaction' => $transaction,
        ]);
    }
}
