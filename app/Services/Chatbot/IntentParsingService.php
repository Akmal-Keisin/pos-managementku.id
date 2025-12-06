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
}
