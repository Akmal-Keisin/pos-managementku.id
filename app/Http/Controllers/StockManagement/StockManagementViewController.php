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

		// Apply filters
		if ($request->has('product_id') && $request->product_id) {
			$query->where('product_id', $request->product_id);
		}

		if ($request->has('user_id') && $request->user_id) {
			$query->where('user_id', $request->user_id);
		}

		if ($request->has('type') && $request->type) {
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
			'filters' => $request->only(['product_id', 'user_id', 'type']),
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
