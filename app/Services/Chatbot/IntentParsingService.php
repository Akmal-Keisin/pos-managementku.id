<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Log;

/**
 * IntentParsingService
 *
 * Responsible for parsing user input and identifying intent patterns
 * Extracts parameters from natural language input using regex patterns
 */
class IntentParsingService
{
    /**
     * Check if input is a product addition request
     */
    public function isAddProductIntent(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/tambah.*produk/i', $normalized) ||
            preg_match('/add.*product/i', $normalized) ||
            preg_match('/create.*product/i', $normalized);
    }

    /**
     * Check if input is a product restock request
     */
    public function isRestockProductIntent(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/(restok|restock).*produk/i', $normalized) ||
            preg_match('/(restock|update|increase).*stock/i', $normalized);
    }

    /**
     * Check if input is a confirmation (YES)
     */
    public function isConfirmation(string $input): bool
    {
        $normalized = trim(strtolower($input));

        return in_array($normalized, ['ya', 'iya', 'yes', 'y']);
    }

    /**
     * Check if input is a rejection (NO)
     */
    public function isRejection(string $input): bool
    {
        $normalized = trim(strtolower($input));

        return in_array($normalized, ['tidak', 'no', 'n']);
    }

    /**
     * Check if input is a cancellation
     */
    public function isCancellation(string $input): bool
    {
        $normalized = trim(strtolower($input));

        return in_array($normalized, ['batal', 'cancel', 'stop']);
    }

    /**
     * Check if input is a numeric selection (for product choice)
     */
    public function isNumericSelection(string $input): bool
    {
        return preg_match('/^\d+$/', trim($input)) === 1;
    }

    /**
     * Extract product data from add product intent
     * Expected format: "tambah produk nama <name> harga <price> stok <stock> deskripsi <desc>"
     */
    public function extractProductData(string $input): array
    {
        $data = [
            'name' => null,
            'price' => 0,
            'current_stock' => 0,
            'description' => '',
        ];

        // Extract product name
        if (preg_match('/(?:nama|name)\s+([a-zA-Z0-9 ]+?)(?=\s+(harga|price|stok|stock|deskripsi|description)|$)/i', $input, $match)) {
            $data['name'] = $match[1] ?? null;
        }

        // Extract price
        if (preg_match('/(?:harga|price)\s+([0-9]+)/i', $input, $match)) {
            $data['price'] = (int)($match[1] ?? 0);
        }

        // Extract stock
        if (preg_match('/(?:stok|stock)\s+([0-9]+)/i', $input, $match)) {
            $data['current_stock'] = (int)($match[1] ?? 0);
        }

        // Extract description
        if (preg_match('/(?:deskripsi|description)\s+(.+)$/i', $input, $match)) {
            $data['description'] = $match[1] ?? '';
        }

        if (!app()->environment('production')) {
            Log::debug('IntentParsingService: Extracted product data', $data);
        }

        return $data;
    }

    /**
     * Extract restock quantity from input
     * Expected format: "restock ... stok <amount>"
     */
    public function extractRestockAmount(string $input): ?int
    {
        if (preg_match('/(?:stok|stock)\s+(\d+)/i', $input, $match)) {
            $amount = (int)($match[1] ?? null);

            if (!app()->environment('production')) {
                Log::debug('IntentParsingService: Extracted restock amount', ['amount' => $amount]);
            }

            return $amount;
        }

        if (!app()->environment('production')) {
            Log::debug('IntentParsingService: No restock amount found in input');
        }

        return null;
    }

    /**
     * Extract product name from restock request
     * Removes numbers and keywords from input to get product name
     */
    public function extractProductNameFromRestock(string $input): string
    {
        $normalized = strtolower($input);

        // Remove numbers
        $productName = preg_replace('/[0-9]+/', '', $normalized);

        // Remove restock keywords
        $productName = str_ireplace(
            ['restok produk', 'restock product', 'update stock', 'increase stock'],
            '',
            $productName
        );

        $productName = trim($productName);

        if (!app()->environment('production')) {
            Log::debug('IntentParsingService: Extracted product name from restock', ['product_name' => $productName]);
        }

        return $productName;
    }

    /**
     * Convert numeric selection to array index
     */
    public function getSelectionIndex(string $input): int
    {
        return (int)trim($input) - 1;
    }

    /**
     * Check if input is a low stock query
     */
    public function isLowStockQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/(stok|stock)\s+(rendah|low)/i', $normalized) ||
            preg_match('/(produk|barang).*stok.*rendah/i', $normalized) ||
            preg_match('/stok.*di bawah/i', $normalized) ||
            preg_match('/perlu.*restock/i', $normalized);
    }

    /**
     * Check if input is a sales/revenue query
     */
    public function isSalesQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/(penjualan|sales|terjual|omset|revenue)/i', $normalized) ||
            preg_match('/(hari ini|hari|bulan|minggu).*penjualan/i', $normalized) ||
            preg_match('/(total|berapa).*penjualan/i', $normalized);
    }

    /**
     * Check if input is asking for best sellers
     */
    public function isBestSellerQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/best\s+seller/i', $normalized) ||
            preg_match('/(terlaris|paling laku|best selling)/i', $normalized) ||
            preg_match('/produk.*populer/i', $normalized);
    }

    /**
     * Check if input is asking for transaction/history query
     */
    public function isTransactionQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/(transaksi|riwayat|history|histori)/i', $normalized) ||
            preg_match('/(penjualan|sales).*riwayat/i', $normalized) ||
            preg_match('/lihat.*transaksi/i', $normalized);
    }

    /**
     * Check if input is asking for cashier performance
     */
    public function isCashierPerformanceQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/(kasir|cashier).*performa/i', $normalized) ||
            preg_match('/performa.*(kasir|cashier)/i', $normalized) ||
            preg_match('/(top|terbaik).*kasir/i', $normalized);
    }

    /**
     * Check if input is asking for stock analysis/status
     */
    public function isStockAnalysisQuery(string $input): bool
    {
        $normalized = strtolower($input);

        return preg_match('/analisis.*stok/i', $normalized) ||
            preg_match('/status.*stok/i', $normalized) ||
            preg_match('/(stok|stock).*bagaimana/i', $normalized);
    }

    /**
     * Extract date parameter from input
     * Returns: today, yesterday, week, month, or null
     */
    public function extractDateParam(string $input): ?string
    {
        $normalized = strtolower($input);

        if (preg_match('/(hari ini|today)/i', $normalized)) {
            return 'today';
        } elseif (preg_match('/(kemarin|yesterday)/i', $normalized)) {
            return 'yesterday';
        } elseif (preg_match('/(minggu|week)/i', $normalized)) {
            return 'week';
        } elseif (preg_match('/(bulan|month)/i', $normalized)) {
            return 'month';
        }

        return null;
    }

    /**
     * Extract product name from query
     */
    public function extractProductNameFromQuery(string $input): ?string
    {
        // Try to find quoted product name
        if (preg_match('/["\']([^"\']+)["\']/', $input, $match)) {
            return trim($match[1]);
        }

        // Remove common keywords and extract remaining text
        $cleaned = preg_replace(
            [
                '/stok\s+(rendah|lowbagaimana)/i',
                '/status.*stok\s+/i',
                '/bagaimana.*stok\s+/i',
            ],
            '',
            $input
        );

        $cleaned = trim($cleaned);

        return !empty($cleaned) ? $cleaned : null;
    }
}
