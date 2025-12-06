<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Log;

/**
 * Minimal read-only DB tool service for chatbot queries.
 * Only performs whitelisted, read-only queries and returns structured results.
 */
class DBToolService
{
    /**
     * Search products by content terms (name or sku) and return formatted lines.
     * Returns array of associative rows with keys: id, name, sku, current_stock, price
     */
    public function searchProducts(string $content, int $limit = 10): array
    {
        $content = (string) $content;
        if (trim($content) === '') return [];
        // Normalize input: lowercase and replace non-alnum with spaces
        $normalized = mb_strtolower($content);
        $normalized = preg_replace('/[^a-z0-9\\p{L}]+/u', ' ', $normalized);

        // Only attempt when user clearly requests inventory/product info
        if (!preg_match('/\\b(stok|stock|produk|product|tersedia|available|harga|price)\\b/i', $normalized)) {
            return [];
        }

        $words = preg_split('/\\s+/', $normalized);
        // accept shorter tokens (>=2) to match abbreviations or short names
        $terms = array_values(array_filter(array_map('trim', $words), fn($w) => strlen($w) >= 2));

        // Log normalized input and terms for debugging
        if (!app()->environment('production')) {
            Log::debug('DBToolService::searchProducts input', ['normalized' => $normalized, 'terms' => $terms]);
        }

        $query = Product::query();
        $query->where(function ($q) use ($terms) {
            foreach ($terms as $t) {
                $tEsc = "%" . strtolower($t) . "%";
                $q->orWhereRaw('LOWER(name) LIKE ?', [$tEsc])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$tEsc])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$tEsc]);
            }
        });

        // Log SQL for debugging before executing
        try {
            if (!app()->environment('production')) {
                Log::debug('DBToolService::searchProducts SQL', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $found = $query->limit($limit)->get(['id', 'name', 'sku', 'current_stock', 'price', 'description'])->toArray();

        // If nothing matched, try AND-based match: all terms must appear in the name
        if (empty($found) && count($terms) > 1) {
            $andQuery = Product::query();
            foreach ($terms as $t) {
                $tEsc = '%' . strtolower($t) . '%';
                $andQuery->whereRaw('LOWER(name) LIKE ?', [$tEsc]);
            }
            try {
                if (!app()->environment('production')) {
                    Log::debug('DBToolService::searchProducts trying AND-match', ['terms' => $terms, 'sql' => $andQuery->toSql(), 'bindings' => $andQuery->getBindings()]);
                }
            } catch (\Throwable $e) {
            }
            $found = $andQuery->limit($limit)->get(['id', 'name', 'sku', 'current_stock', 'price', 'description'])->toArray();
        }

        // If nothing matched, try fallback: remove keywords and search whole phrase
        if (empty($found)) {
            $fallback = preg_replace('/\\b(stok|stock|produk|product|tersedia|available|harga|price)\\b/i', '', $normalized);
            $fallback = trim(preg_replace('/\\s+/', ' ', $fallback));
            if (strlen($fallback) >= 3) {
                // wildcard across words: e.g. 'pocari sweat' -> '%pocari%sweat%'
                $wild = '%' . str_replace(' ', '%', $fallback) . '%';
                $found = Product::where('name', 'like', $wild)
                    ->orWhere('description', 'like', $wild)
                    ->orWhere('sku', 'like', "%{$fallback}%")
                    ->limit($limit)
                    ->get(['id', 'name', 'sku', 'current_stock', 'price', 'description'])
                    ->toArray();
            }
        }

        $rows = [];
        foreach ($found as $p) {
            $stock = $p['current_stock'] ?? null;
            if ($stock === null) {
                try {
                    $stock = StockHistory::where('product_id', $p['id'])->sum('quantity');
                } catch (\Throwable $e) {
                    $stock = null;
                }
            }

            $rows[] = [
                'id' => $p['id'],
                'name' => $p['name'] ?? null,
                'sku' => $p['sku'] ?? null,
                'current_stock' => $stock,
                'price' => $p['price'] ?? null,
            ];
        }

        if (!empty($rows)) {
            if (!app()->environment('production')) {
                Log::debug('DBToolService::searchProducts', ['count' => count($rows), 'terms' => $terms]);
            }
        }

        return $rows;
    }

    /**
     * Format product rows into human-readable lines for model prompt.
     */
    public function formatProductLines(array $rows): array
    {
        $lines = [];
        foreach ($rows as $p) {
            $name = $p['name'] ?? 'Unknown';
            $sku = $p['sku'] ?? null;
            $stock = $p['current_stock'] ?? null;
            $price = $p['price'] ?? null;

            $line = "Product: {$name}";
            if ($sku) $line .= " (SKU: {$sku})";
            if ($stock !== null) $line .= ", Stock: {$stock}";
            if ($price !== null) $line .= ", Price: {$price}";

            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * Run a whitelisted intent and return structured rows.
     * Supported intents: product_lookup, stock_lookup, transactions_recent
     */
    public function runIntent(string $intent, array $params = []): array
    {
        $intent = strtolower(trim($intent));
        switch ($intent) {
            case 'product_lookup':
            case 'stock_lookup':
                $q = $params['q'] ?? ($params['query'] ?? '');
                return $this->searchProducts((string) $q, (int) ($params['limit'] ?? 10));
            case 'transactions_recent':
                // simple recent transactions summary
                $limit = (int) ($params['limit'] ?? 10);
                $rows = \App\Models\Transaction::orderBy('created_at', 'desc')->limit($limit)->get(['id', 'user_id', 'total', 'status', 'created_at'])->toArray();
                return $rows;
            default:
                return [];
        }
    }
}
