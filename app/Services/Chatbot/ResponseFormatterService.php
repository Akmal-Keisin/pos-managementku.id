<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Log;

/**
 * ResponseFormatterService
 *
 * Formats chatbot responses into various types for rich display
 * Supports text, tables, confirmations, and action-driven responses
 */
class ResponseFormatterService
{
    /**
     * Format response as a table for structured data display
     *
     * @param array $data Data rows
     * @param array $columns Column definitions with 'key' and 'label'
     * @param string $title Optional title
     * @return array Structured table response
     */
    public function formatAsTable(array $data, array $columns = [], string $title = ''): array
    {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as table', [
                'row_count' => count($data),
                'column_count' => count($columns),
            ]);
        }

        // Auto-detect columns if not provided
        if (empty($columns) && !empty($data)) {
            $firstRow = $data[0];
            $columns = array_map(fn($key) => [
                'key' => $key,
                'label' => ucfirst(str_replace('_', ' ', $key)),
            ], array_keys((array)$firstRow));
        }

        return [
            'type' => 'table',
            'title' => $title,
            'columns' => $columns,
            'data' => $data,
            'row_count' => count($data),
        ];
    }

    /**
     * Format response as a product list
     *
     * @param array $products Array of product data
     * @param string $title Optional title
     * @return array Product list response
     */
    public function formatAsProductList(array $products, string $title = 'Daftar Produk'): array
    {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as product list', [
                'product_count' => count($products),
            ]);
        }

        $items = array_map(function ($product) {
            return [
                'id' => $product['id'] ?? $product['product_id'] ?? null,
                'name' => $product['name'] ?? $product['product_name'] ?? 'Unknown',
                'stock' => $product['current_stock'] ?? $product['quantity'] ?? 0,
                'price' => $product['price'] ?? 0,
                'description' => $product['description'] ?? '',
                'status_label' => $this->getStockStatusLabel(
                    $product['current_stock'] ?? 0
                ),
            ];
        }, $products);

        return [
            'type' => 'product_list',
            'title' => $title,
            'items' => $items,
            'count' => count($items),
        ];
    }

    /**
     * Format response as a multi-step confirmation
     *
     * @param string $message Main confirmation message
     * @param array $details Key-value pairs to display
     * @param array $actions Available actions (e.g., ['yes', 'no', 'cancel'])
     * @param string $title Optional title
     * @return array Confirmation response
     */
    public function formatAsConfirmation(
        string $message,
        array $details = [],
        array $actions = ['yes', 'no'],
        string $title = 'Konfirmasi'
    ): array {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as confirmation', [
                'details_count' => count($details),
                'action_count' => count($actions),
            ]);
        }

        return [
            'type' => 'confirmation',
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'actions' => $actions,
            'requires_response' => true,
        ];
    }

    /**
     * Format response with actionable buttons/options
     *
     * @param string $message Main message
     * @param array $actions Array of action buttons with 'label' and 'action'
     * @param string $title Optional title
     * @return array Action-driven response
     */
    public function formatAsActionable(string $message, array $actions = [], string $title = ''): array
    {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as actionable', [
                'action_count' => count($actions),
            ]);
        }

        return [
            'type' => 'actionable',
            'title' => $title,
            'message' => $message,
            'actions' => $actions,
        ];
    }

    /**
     * Format response as an operation result
     *
     * @param bool $success Whether operation succeeded
     * @param string $message Result message
     * @param array $details Additional details about the operation
     * @param string $operationType Type of operation (add_product, restock, etc.)
     * @return array Operation result response
     */
    public function formatAsOperationResult(
        bool $success,
        string $message,
        array $details = [],
        string $operationType = ''
    ): array {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as operation result', [
                'success' => $success,
                'operation_type' => $operationType,
            ]);
        }

        return [
            'type' => 'operation_result',
            'success' => $success,
            'message' => $message,
            'operation_type' => $operationType,
            'details' => $details,
            'icon' => $success ? '✅' : '❌',
        ];
    }

    /**
     * Format response as a summary/metric display
     *
     * @param array $metrics Key-value pairs of metrics to display
     * @param string $title Title of the summary
     * @return array Summary response
     */
    public function formatAsSummary(array $metrics = [], string $title = 'Ringkasan'): array
    {
        if (!app()->environment('production')) {
            Log::debug('ResponseFormatterService: Formatting as summary', [
                'metric_count' => count($metrics),
            ]);
        }

        return [
            'type' => 'summary',
            'title' => $title,
            'metrics' => $metrics,
        ];
    }

    /**
     * Format error response
     *
     * @param string $message Error message
     * @param string $errorCode Optional error code
     * @return array Error response
     */
    public function formatAsError(string $message, string $errorCode = ''): array
    {
        Log::warning('ResponseFormatterService: Formatting error response', [
            'error_code' => $errorCode,
            'message' => $message,
        ]);

        return [
            'type' => 'error',
            'message' => $message,
            'error_code' => $errorCode,
            'icon' => '❌',
        ];
    }

    /**
     * Format as plain text response
     *
     * @param string $message The message text
     * @return array Plain text response
     */
    public function formatAsText(string $message): array
    {
        return [
            'type' => 'text',
            'content' => $message,
        ];
    }

    /**
     * Get stock status label with styling
     *
     * @param int $stock Current stock level
     * @return string Formatted status label
     */
    private function getStockStatusLabel(int $stock): string
    {
        if ($stock <= 5) {
            return '🔴 Kritis';
        } elseif ($stock <= 10) {
            return '🟡 Rendah';
        } elseif ($stock <= 20) {
            return '🟡 Normal';
        } else {
            return '🟢 Sehat';
        }
    }

    /**
     * Convert structured response to text fallback
     * Used for clients that don't support structured responses
     *
     * @param array $response Structured response
     * @return string Plain text representation
     */
    public function toTextFallback(array $response): string
    {
        $type = $response['type'] ?? 'text';

        return match ($type) {
            'table' => $this->tableToText($response),
            'product_list' => $this->productListToText($response),
            'confirmation' => $this->confirmationToText($response),
            'summary' => $this->summaryToText($response),
            'error' => $response['message'] ?? 'An error occurred',
            default => $response['content'] ?? $response['message'] ?? 'No response',
        };
    }

    /**
     * Convert table response to text
     *
     * @param array $response Table response
     * @return string Plain text table
     */
    private function tableToText(array $response): string
    {
        $lines = [];

        if (!empty($response['title'])) {
            $lines[] = "📊 " . $response['title'];
            $lines[] = str_repeat("=", strlen($response['title']) + 3);
        }

        if (empty($response['data'])) {
            return implode("\n", $lines) . "\n(Tidak ada data)";
        }

        $columns = $response['columns'] ?? [];

        foreach ($response['data'] as $row) {
            foreach ($columns as $col) {
                $key = $col['key'] ?? '';
                $label = $col['label'] ?? $key;
                $value = $row[$key] ?? '-';
                $lines[] = "$label: $value";
            }
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * Convert product list response to text
     *
     * @param array $response Product list response
     * @return string Plain text product list
     */
    private function productListToText(array $response): string
    {
        $lines = [];

        if (!empty($response['title'])) {
            $lines[] = "📦 " . $response['title'];
        }

        foreach ($response['items'] ?? [] as $idx => $product) {
            $lines[] = sprintf(
                "%d. %s - Stok: %d (Harga: Rp%s) %s",
                $idx + 1,
                $product['name'] ?? 'Unknown',
                $product['stock'] ?? 0,
                number_format($product['price'] ?? 0, 0, ',', '.'),
                $product['status_label'] ?? ''
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Convert confirmation response to text
     *
     * @param array $response Confirmation response
     * @return string Plain text confirmation
     */
    private function confirmationToText(array $response): string
    {
        $lines = [];

        if (!empty($response['title'])) {
            $lines[] = "❓ " . $response['title'];
        }

        $lines[] = $response['message'] ?? '';

        foreach ($response['details'] ?? [] as $key => $value) {
            $lines[] = sprintf("  %s: %s", ucfirst($key), $value);
        }

        if (!empty($response['actions'])) {
            $lines[] = "\nPilihan: " . implode(", ", $response['actions']);
        }

        return implode("\n", $lines);
    }

    /**
     * Convert summary response to text
     *
     * @param array $response Summary response
     * @return string Plain text summary
     */
    private function summaryToText(array $response): string
    {
        $lines = [];

        if (!empty($response['title'])) {
            $lines[] = "📈 " . $response['title'];
        }

        foreach ($response['metrics'] ?? [] as $key => $value) {
            $lines[] = sprintf("  %s: %s", ucfirst($key), $value);
        }

        return implode("\n", $lines);
    }
}
