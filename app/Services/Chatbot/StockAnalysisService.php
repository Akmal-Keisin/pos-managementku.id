<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * StockAnalysisService
 *
 * Provides stock-related analytics and analysis for the chatbot
 * Handles inventory levels, trends, and predictions
 */
class StockAnalysisService
{
    /**
     * Get comprehensive stock status for a product
     *
     * @param int $productId Product ID
     * @return array Array with stock details and analysis
     */
    public function getStockStatus(int $productId): array
    {
        if (!app()->environment('production')) {
            Log::debug('StockAnalysisService: Fetching stock status', [
                'product_id' => $productId,
            ]);
        }

        try {
            $product = Product::find($productId);

            if (!$product) {
                if (!app()->environment('production')) {
                    Log::warning('StockAnalysisService: Product not found', [
                        'product_id' => $productId,
                    ]);
                }
                return ['error' => 'Product not found'];
            }

            // Get last month's sales average
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $monthlySalesAvg = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->where('product_id', $productId)
                ->where('transactions.created_at', '>=', $thirtyDaysAgo)
                ->selectRaw('COUNT(*) / 30 as daily_average')
                ->value('daily_average') ?? 0;

            // Calculate days of stock
            $daysOfStock = $monthlySalesAvg > 0
                ? (int)ceil($product->current_stock / $monthlySalesAvg)
                : 999;

            // Determine status
            $status = $this->getStockStatusLabel($daysOfStock, $product->current_stock);

            $data = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'current_stock' => (int)$product->current_stock,
                'daily_sales_average' => (float)$monthlySalesAvg,
                'days_of_stock' => $daysOfStock,
                'status' => $status,
                'needs_restock' => $daysOfStock <= 7,
            ];

            if (!app()->environment('production')) {
                Log::debug('StockAnalysisService: Stock status retrieved', [
                    'product_id' => $productId,
                    'status' => $status,
                    'days_of_stock' => $daysOfStock,
                ]);
            }

            return $data;
        } catch (\Throwable $exception) {
            Log::error('StockAnalysisService: Failed to fetch stock status', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'product_id' => $productId,
            ]);

            return ['error' => 'Failed to fetch stock status'];
        }
    }

    /**
     * Analyze stock trends for a product
     *
     * @param int $productId Product ID
     * @param int $days Days to analyze
     * @return array Array with trend analysis
     */
    public function analyzeStockTrends(int $productId, int $days = 30): array
    {
        if (!app()->environment('production')) {
            Log::debug('StockAnalysisService: Analyzing stock trends', [
                'product_id' => $productId,
                'days' => $days,
            ]);
        }

        try {
            $startDate = Carbon::now()->subDays($days);

            $histories = StockHistory::where('product_id', $productId)
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'asc')
                ->get(['type', 'quantity', 'created_at']);

            $increases = 0;
            $decreases = 0;
            $totalChanged = 0;

            foreach ($histories as $history) {
                if ($history->type === 'increase') {
                    $increases += $history->quantity;
                } else {
                    $decreases += $history->quantity;
                }
                $totalChanged += $history->quantity;
            }

            $trend = $increases > $decreases ? 'increasing' : 'decreasing';

            $data = [
                'product_id' => $productId,
                'period_days' => $days,
                'total_increased' => (int)$increases,
                'total_decreased' => (int)$decreases,
                'net_change' => (int)($increases - $decreases),
                'trend' => $trend,
                'is_stable' => abs($increases - $decreases) < ($increases + $decreases) * 0.2,
            ];

            if (!app()->environment('production')) {
                Log::debug('StockAnalysisService: Stock trends analyzed', [
                    'product_id' => $productId,
                    'trend' => $trend,
                ]);
            }

            return $data;
        } catch (\Throwable $exception) {
            Log::error('StockAnalysisService: Failed to analyze stock trends', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'product_id' => $productId,
            ]);

            return ['error' => 'Failed to analyze trends'];
        }
    }

    /**
     * Predict which products need reordering soon
     *
     * @param int $lookAheadDays Days to predict ahead
     * @return array Array of products needing reorder
     */
    public function predictReorderNeeds(int $lookAheadDays = 7): array
    {
        if (!app()->environment('production')) {
            Log::debug('StockAnalysisService: Predicting reorder needs', [
                'look_ahead_days' => $lookAheadDays,
            ]);
        }

        try {
            $products = Product::all();
            $needsReorder = [];

            foreach ($products as $product) {
                $status = $this->getStockStatus($product->id);

                if (isset($status['error'])) {
                    continue;
                }

                if ($status['days_of_stock'] <= $lookAheadDays) {
                    $needsReorder[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'current_stock' => $status['current_stock'],
                        'days_of_stock' => $status['days_of_stock'],
                        'daily_sales_average' => $status['daily_sales_average'],
                        'recommended_order_qty' => (int)ceil(
                            $status['daily_sales_average'] * 30
                        ),
                    ];
                }
            }

            usort($needsReorder, function ($a, $b) {
                return $a['days_of_stock'] <=> $b['days_of_stock'];
            });

            if (!app()->environment('production')) {
                Log::debug('StockAnalysisService: Reorder prediction complete', [
                    'products_needing_reorder' => count($needsReorder),
                ]);
            }

            return $needsReorder;
        } catch (\Throwable $exception) {
            Log::error('StockAnalysisService: Failed to predict reorder needs', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [];
        }
    }

    /**
     * Get critical stock alerts
     *
     * @return array Array of critical stock items
     */
    public function getCriticalStockAlerts(): array
    {
        if (!app()->environment('production')) {
            Log::debug('StockAnalysisService: Fetching critical stock alerts');
        }

        try {
            $critical = Product::where('current_stock', '<=', 5)
                ->orderBy('current_stock', 'asc')
                ->get(['id', 'name', 'current_stock', 'price'])
                ->toArray();

            if (!app()->environment('production')) {
                Log::debug('StockAnalysisService: Critical alerts retrieved', [
                    'count' => count($critical),
                ]);
            }

            return $critical;
        } catch (\Throwable $exception) {
            Log::error('StockAnalysisService: Failed to fetch critical alerts', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [];
        }
    }

    /**
     * Format stock status report for display
     *
     * @param array $status Stock status data
     * @return string Formatted text for display
     */
    public function formatStockStatusReport(array $status): string
    {
        if (isset($status['error'])) {
            return "❌ {$status['error']}";
        }

        $emoji = match ($status['status']) {
            'critical' => '🔴',
            'low' => '🟡',
            'normal' => '🟢',
            'healthy' => '🟢',
            default => '⚪',
        };

        return sprintf(
            "%s *%s*\n\n" .
                "Stok Saat Ini: %d unit\n" .
                "Rata-rata Terjual/hari: %.2f unit\n" .
                "Estimasi Tersisa: %d hari\n" .
                "Status: %s\n\n" .
                "%s",
            $emoji,
            $status['product_name'],
            $status['current_stock'],
            $status['daily_sales_average'],
            $status['days_of_stock'],
            ucfirst($status['status']),
            $status['needs_restock'] ? "⚠️ Butuh restock segera!" : "✅ Stok cukup"
        );
    }

    /**
     * Determine stock status label
     *
     * @param int $daysOfStock Days of stock remaining
     * @param int $currentStock Current stock level
     * @return string Status label
     */
    private function getStockStatusLabel(int $daysOfStock, int $currentStock): string
    {
        if ($currentStock <= 5) {
            return 'critical';
        } elseif ($daysOfStock <= 7 || $currentStock <= 10) {
            return 'low';
        } elseif ($daysOfStock <= 14) {
            return 'normal';
        } else {
            return 'healthy';
        }
    }
}
