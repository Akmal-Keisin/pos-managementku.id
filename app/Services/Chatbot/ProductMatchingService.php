<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * ProductMatchingService
 *
 * Responsible for fuzzy searching and matching products based on user input
 * Uses word matching with scoring to find similar product names
 */
class ProductMatchingService
{
    /**
     * Find product candidates based on fuzzy matching
     *
     * @param string $input Product name or partial name from user
     * @param int $maxResults Maximum number of results to return
     * @return array Array of candidates with product model and match score
     */
    public function findCandidates(string $input, int $maxResults = 5): array
    {
        $input = strtolower(trim($input));

        $products = Product::all();

        if ($products->isEmpty()) {
            if (!app()->environment('production')) {
                Log::debug('ProductMatchingService: No products found in database');
            }
            return [];
        }

        // Normalize input: extract meaningful words (3+ characters)
        $normalized = preg_replace('/[^a-z0-9 ]/i', ' ', $input);
        $words = array_filter(explode(' ', $normalized));
        $words = array_filter($words, fn($w) => strlen($w) >= 3);

        if (empty($words)) {
            if (!app()->environment('production')) {
                Log::debug('ProductMatchingService: No keywords extracted from input', ['input' => $input]);
            }
            return [];
        }

        $candidates = $this->scoreProducts($products, $words);

        if (empty($candidates)) {
            if (!app()->environment('production')) {
                Log::debug('ProductMatchingService: No matching products found', ['keywords' => $words]);
            }
            return [];
        }

        // Sort by score (descending), then by name (ascending)
        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($a['product']->name, $b['product']->name);
            }
            return $b['score'] <=> $a['score'];
        });

        $results = array_slice($candidates, 0, $maxResults);

        if (!app()->environment('production')) {
            Log::debug('ProductMatchingService: Found candidates', [
                'input' => $input,
                'count' => count($results),
                'keywords' => $words,
            ]);
        }

        return $results;
    }

    /**
     * Score each product based on keyword matches
     *
     * @param Collection $products Collection of Product models
     * @param array $keywords Keywords to match against
     * @return array Array of products with scores
     */
    private function scoreProducts(Collection $products, array $keywords): array
    {
        $candidates = [];

        foreach ($products as $product) {
            $score = $this->calculateScore($product->name, $keywords);

            if ($score > 0) {
                $candidates[] = [
                    'product' => $product,
                    'score' => $score,
                ];
            }
        }

        return $candidates;
    }

    /**
     * Calculate match score for a product name
     * Each keyword match adds 1 point to the score
     *
     * @param string $productName Product name to check
     * @param array $keywords Keywords to match
     * @return int Match score
     */
    private function calculateScore(string $productName, array $keywords): int
    {
        $score = 0;
        $name = strtolower($productName);

        foreach ($keywords as $keyword) {
            if (str_contains($name, $keyword)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Format product candidates for user display
     *
     * @param array $candidates Array of candidates from findCandidates()
     * @return string Formatted text for display
     */
    public function formatCandidatesForDisplay(array $candidates): string
    {
        return collect($candidates)
            ->map(function ($candidate, $idx) {
                $product = $candidate['product'];
                $number = $idx + 1;

                $text = "[{$number}] {$product->name}";

                if (isset($product->price)) {
                    $text .= " — Harga: {$product->price}";
                }

                if (isset($product->current_stock)) {
                    $text .= " — Stok: {$product->current_stock}";
                }

                return $text;
            })
            ->implode("\n");
    }

    /**
     * Convert candidates array to simplified format for JSON storage
     *
     * @param array $candidates Array of candidates from findCandidates()
     * @return array Simplified candidate data
     */
    public function serializeCandidates(array $candidates): array
    {
        return array_map(function ($candidate) {
            $product = $candidate['product'];
            return [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }, $candidates);
    }

    /**
     * Get product by ID for confirmation
     *
     * @param int $productId Product ID to retrieve
     * @return Product|null Product model or null if not found
     */
    public function getProductById(int $productId): ?Product
    {
        $product = Product::find($productId);

        if (!$product) {
            if (!app()->environment('production')) {
                Log::warning('ProductMatchingService: Product not found', ['product_id' => $productId]);
            }
        }

        return $product;
    }
}
