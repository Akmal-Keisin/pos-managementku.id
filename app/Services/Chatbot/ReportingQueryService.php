<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ReportingQueryService
 *
 * Provides business intelligence and reporting queries for the chatbot
 * Handles sales data, product performance, user metrics, and trends
 */
class ReportingQueryService
{
	/**
	 * Get products with stock below threshold
	 *
	 * @param int $threshold Stock level threshold
	 * @param int $limit Maximum results to return
	 * @return array Array of low-stock products
	 */
	public function getLowStockProducts(int $threshold = 10, int $limit = 20): array
	{
		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching low stock products', [
				'threshold' => $threshold,
				'limit' => $limit,
			]);
		}

		try {
			$products = Product::where('current_stock', '<=', $threshold)
				->orderBy('current_stock', 'asc')
				->limit($limit)
				->get(['id', 'name', 'sku', 'current_stock', 'price'])
				->toArray();

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Low stock products retrieved', [
					'count' => count($products),
				]);
			}

			return $products;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch low stock products', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [];
		}
	}

	/**
	 * Get best selling products
	 *
	 * @param int $days Days to look back
	 * @param int $limit Maximum results
	 * @return array Array of best sellers with sales count and revenue
	 */
	public function getBestSellingProducts(int $days = 30, int $limit = 10): array
	{
		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching best sellers', [
				'days' => $days,
				'limit' => $limit,
			]);
		}

		try {
			$startDate = Carbon::now()->subDays($days);

			$products = TransactionDetail::select(
				'product_id',
				DB::raw('SUM(quantity) as total_quantity'),
				DB::raw('SUM(total) as total_revenue'),
				DB::raw('COUNT(DISTINCT transaction_id) as transaction_count')
			)
				->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
				->where('transactions.created_at', '>=', $startDate)
				->groupBy('product_id')
				->orderBy('total_quantity', 'desc')
				->limit($limit)
				->get();

			// Load product details
			$results = $products->map(function ($item) {
				$product = Product::find($item->product_id);
				return [
					'product_id' => $item->product_id,
					'product_name' => $product?->name ?? 'Unknown',
					'quantity_sold' => (int)$item->total_quantity,
					'revenue' => (float)$item->total_revenue,
					'transaction_count' => (int)$item->transaction_count,
				];
			})->toArray();

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Best sellers retrieved', [
					'count' => count($results),
				]);
			}

			return $results;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch best sellers', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [];
		}
	}

	/**
	 * Get daily sales amount
	 *
	 * @param Carbon|null $date Date to check (defaults to today)
	 * @return array Array with total amount and transaction count
	 */
	public function getDailySalesAmount(?Carbon $date = null): array
	{
		$date = $date ?? Carbon::today();

		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching daily sales', [
				'date' => $date->format('Y-m-d'),
			]);
		}

		try {
			$result = Transaction::whereDate('created_at', $date)
				->select(
					DB::raw('SUM(total) as total_amount'),
					DB::raw('COUNT(*) as transaction_count')
				)
				->first();

			$data = [
				'date' => $date->format('Y-m-d'),
				'total_amount' => (float)($result?->total_amount ?? 0),
				'transaction_count' => (int)($result?->transaction_count ?? 0),
			];

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Daily sales retrieved', [
					'total' => $data['total_amount'],
					'count' => $data['transaction_count'],
				]);
			}

			return $data;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch daily sales', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [
				'date' => $date->format('Y-m-d'),
				'total_amount' => 0,
				'transaction_count' => 0,
			];
		}
	}

	/**
	 * Get weekly sales breakdown
	 *
	 * @return array Array with daily breakdowns
	 */
	public function getWeeklySalesBreakdown(): array
	{
		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching weekly sales breakdown');
		}

		try {
			$days = [];
			for ($i = 6; $i >= 0; $i--) {
				$date = Carbon::now()->subDays($i);
				$days[] = $this->getDailySalesAmount($date);
			}

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Weekly breakdown retrieved', [
					'week_total' => array_sum(array_column($days, 'total_amount')),
				]);
			}

			return $days;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch weekly breakdown', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [];
		}
	}

	/**
	 * Get monthly revenue
	 *
	 * @param int|null $month Month number (1-12), defaults to current month
	 * @param int|null $year Year, defaults to current year
	 * @return array Array with revenue data
	 */
	public function getMonthlyRevenue(?int $month = null, ?int $year = null): array
	{
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? Carbon::now()->year;

		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching monthly revenue', [
				'month' => $month,
				'year' => $year,
			]);
		}

		try {
			$startDate = Carbon::create($year, $month, 1);
			$endDate = $startDate->copy()->endOfMonth();

			$result = Transaction::whereBetween('created_at', [$startDate, $endDate])
				->select(
					DB::raw('SUM(total) as total_amount'),
					DB::raw('COUNT(*) as transaction_count'),
					DB::raw('AVG(total) as average_transaction')
				)
				->first();

			$data = [
				'month' => $month,
				'year' => $year,
				'total_revenue' => (float)($result?->total_amount ?? 0),
				'transaction_count' => (int)($result?->transaction_count ?? 0),
				'average_transaction' => (float)($result?->average_transaction ?? 0),
			];

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Monthly revenue retrieved', [
					'total' => $data['total_revenue'],
				]);
			}

			return $data;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch monthly revenue', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [
				'month' => $month,
				'year' => $year,
				'total_revenue' => 0,
				'transaction_count' => 0,
				'average_transaction' => 0,
			];
		}
	}

	/**
	 * Get top performing cashiers
	 *
	 * @param int $days Days to look back
	 * @param int $limit Maximum results
	 * @return array Array of cashier performance metrics
	 */
	public function getTopCashiers(int $days = 30, int $limit = 10): array
	{
		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching top cashiers', [
				'days' => $days,
				'limit' => $limit,
			]);
		}

		try {
			$startDate = Carbon::now()->subDays($days);

			$cashiers = Transaction::select(
				'user_id',
				DB::raw('COUNT(*) as transaction_count'),
				DB::raw('SUM(total) as total_sales')
			)
				->where('created_at', '>=', $startDate)
				->groupBy('user_id')
				->orderBy('total_sales', 'desc')
				->limit($limit)
				->get();

			$results = $cashiers->map(function ($item) {
				$user = User::find($item->user_id);
				return [
					'user_id' => $item->user_id,
					'cashier_name' => $user?->name ?? 'Unknown',
					'transaction_count' => (int)$item->transaction_count,
					'total_sales' => (float)$item->total_sales,
					'average_sale' => (float)($item->total_sales / $item->transaction_count),
				];
			})->toArray();

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Top cashiers retrieved', [
					'count' => count($results),
				]);
			}

			return $results;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch top cashiers', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			]);

			return [];
		}
	}

	/**
	 * Get product sales trend
	 *
	 * @param int $productId Product ID
	 * @param int $days Days to look back
	 * @return array Array of daily sales for the product
	 */
	public function getProductSalesTrend(int $productId, int $days = 30): array
	{
		if (!app()->environment('production')) {
			Log::debug('ReportingQueryService: Fetching product sales trend', [
				'product_id' => $productId,
				'days' => $days,
			]);
		}

		try {
			$startDate = Carbon::now()->subDays($days);

			$sales = TransactionDetail::select(
				DB::raw('DATE(transactions.created_at) as date'),
				DB::raw('SUM(quantity) as quantity_sold'),
				DB::raw('SUM(total) as revenue')
			)
				->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
				->where('product_id', $productId)
				->where('transactions.created_at', '>=', $startDate)
				->groupBy(DB::raw('DATE(transactions.created_at)'))
				->orderBy('date', 'asc')
				->get();

			$results = $sales->map(function ($item) {
				return [
					'date' => $item->date,
					'quantity_sold' => (int)$item->quantity_sold,
					'revenue' => (float)$item->revenue,
				];
			})->toArray();

			if (!app()->environment('production')) {
				Log::debug('ReportingQueryService: Product trend retrieved', [
					'count' => count($results),
					'product_id' => $productId,
				]);
			}

			return $results;
		} catch (\Throwable $exception) {
			Log::error('ReportingQueryService: Failed to fetch product sales trend', [
				'error' => $exception->getMessage(),
				'exception' => get_class($exception),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'product_id' => $productId,
			]);

			return [];
		}
	}

	/**
	 * Format low stock products for display
	 *
	 * @param array $products Products array
	 * @return string Formatted text for display
	 */
	public function formatLowStockReport(array $products): string
	{
		if (empty($products)) {
			return "Tidak ada produk dengan stok rendah.";
		}

		$lines = ["📦 *Daftar Produk Stok Rendah:*\n"];

		foreach ($products as $product) {
			$lines[] = sprintf(
				"• %s - Stok: %d (Harga: Rp%s)",
				$product['name'],
				$product['current_stock'],
				number_format($product['price'], 0, ',', '.')
			);
		}

		return implode("\n", $lines);
	}

	/**
	 * Format sales report for display
	 *
	 * @param array $data Sales data
	 * @return string Formatted text for display
	 */
	public function formatSalesReport(array $data): string
	{
		return sprintf(
			"💰 *Laporan Penjualan %s*\n\n" .
				"Total: Rp%s\n" .
				"Transaksi: %d",
			$data['date'] ?? 'Hari Ini',
			number_format($data['total_amount'], 0, ',', '.'),
			$data['transaction_count'] ?? 0
		);
	}
}
