<?php

namespace App\Services\Chatbot;

use App\Models\ChatTopic;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * ConfirmationService
 *
 * Responsible for handling confirmation actions
 * Processes product additions and restocks after user confirmation
 */
class ConfirmationService
{
    /**
     * Handle confirmed product addition
     *
     * @param ChatTopic $topic The chat topic
     * @param array $payload Product data from confirmation_payload
     * @return array Result with success status and message
     */
    public function handleAddProduct(ChatTopic $topic, array $payload): array
    {
        try {
            if (!app()->environment('production')) {
                Log::info('ConfirmationService: Processing product addition', [
                    'product_name' => $payload['name'] ?? 'Unknown',
                ]);
            }

            // Create product with provided data
            $product = Product::create([
                'name' => $payload['name'] ?? 'Unnamed Product',
                'price' => $payload['price'] ?? 0,
                'current_stock' => $payload['current_stock'] ?? 0,
                'description' => $payload['description'] ?? '',
            ]);

            // Clear confirmation state
            $this->clearConfirmationState($topic);

            if (!app()->environment('production')) {
                Log::info('ConfirmationService: Product added successfully', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                ]);
            }

            return [
                'success' => true,
                'message' => "Produk berhasil ditambahkan!",
                'product' => $product,
            ];
        } catch (\Throwable $exception) {
            Log::error('ConfirmationService: Failed to add product', [
                'error' => $exception->getMessage(),
                'payload' => $payload,
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
            ]);

            // Clear confirmation state even on error
            $this->clearConfirmationState($topic);

            return [
                'success' => false,
                'message' => "Gagal menambahkan produk. Silakan coba lagi.",
            ];
        }
    }

    /**
     * Handle confirmed product restock
     *
     * @param ChatTopic $topic The chat topic
     * @param array $payload Restock data (product_id, qty)
     * @return array Result with success status and message
     */
    public function handleRestockProduct(ChatTopic $topic, array $payload): array
    {
        try {
            $productId = $payload['product_id'] ?? null;
            $quantity = (int)($payload['qty'] ?? 0);

            if (!$productId || $quantity <= 0) {
                if (!app()->environment('production')) {
                    Log::warning('ConfirmationService: Invalid restock data', [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ]);
                }

                return [
                    'success' => false,
                    'message' => "Data restock tidak valid. Silakan ulangi.",
                ];
            }

            // Find product
            $product = Product::find($productId);

            if (!$product) {
                if (!app()->environment('production')) {
                    Log::warning('ConfirmationService: Product not found for restock', [
                        'product_id' => $productId,
                    ]);
                }

                $this->clearConfirmationState($topic);

                return [
                    'success' => false,
                    'message' => "Produk tidak ditemukan. Silakan ulangi perintah restock.",
                ];
            }

            // Update stock
            $oldStock = $product->current_stock;
            $product->current_stock += $quantity;
            $product->save();

            // Clear confirmation state
            $this->clearConfirmationState($topic);

            if (!app()->environment('production')) {
                Log::info('ConfirmationService: Stock updated successfully', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $product->current_stock,
                    'quantity_added' => $quantity,
                ]);
            }

            return [
                'success' => true,
                'message' => "Stok untuk produk '{$product->name}' berhasil ditambah sebesar {$quantity}.",
                'product' => $product,
            ];
        } catch (\Throwable $exception) {
            Log::error('ConfirmationService: Failed to restock product', [
                'error' => $exception->getMessage(),
                'payload' => $payload,
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
            ]);

            // Clear confirmation state even on error
            $this->clearConfirmationState($topic);

            return [
                'success' => false,
                'message' => "Gagal melakukan restock. Silakan coba lagi.",
            ];
        }
    }

    /**
     * Process cancellation of confirmation
     *
     * @param ChatTopic $topic The chat topic
     * @return array Result array
     */
    public function handleCancellation(ChatTopic $topic): array
    {
        $action = $topic->confirmation_action;

        if (!app()->environment('production')) {
            Log::info('ConfirmationService: Cancellation processed', [
                'action' => $action,
                'topic_id' => $topic->id,
            ]);
        }

        $this->clearConfirmationState($topic);

        return [
            'success' => true,
            'message' => "Aksi dibatalkan.",
        ];
    }

    /**
     * Clear confirmation state from topic
     *
     * @param ChatTopic $topic The chat topic
     */
    private function clearConfirmationState(ChatTopic $topic): void
    {
        $topic->update([
            'confirmation_action' => null,
            'confirmation_payload' => null,
        ]);

        if (!app()->environment('production')) {
            Log::debug('ConfirmationService: Confirmation state cleared', [
                'topic_id' => $topic->id,
            ]);
        }
    }
}
