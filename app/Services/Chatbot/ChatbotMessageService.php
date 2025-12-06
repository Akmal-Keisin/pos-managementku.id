<?php

namespace App\Services\Chatbot;

use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

/**
 * ChatbotMessageService
 *
 * Main orchestration service for chatbot message processing
 * Coordinates between different services to handle user intents
 */
class ChatbotMessageService
{
    private IntentParsingService $intentService;
    private ProductMatchingService $productService;
    private ConfirmationService $confirmationService;
    private GeminiAIService $geminiService;

    /**
     * Initialize service with dependencies
     */
    public function __construct(
        IntentParsingService $intentService,
        ProductMatchingService $productService,
        ConfirmationService $confirmationService,
        GeminiAIService $geminiService
    ) {
        $this->intentService = $intentService;
        $this->productService = $productService;
        $this->confirmationService = $confirmationService;
        $this->geminiService = $geminiService;
    }

    /**
     * Process user message and generate response
     *
     * @param ChatTopic $topic The chat topic
     * @param ChatMessage $userMessage The user's message
     * @param string $userContent The content of the user's message
     * @return string Assistant response text
     */
    public function processMessage(ChatTopic $topic, ChatMessage $userMessage, string $userContent): string
    {
        $trimmedInput = trim(strtolower($userContent));

        if (!app()->environment('production')) {
            Log::info('ChatbotMessageService: Processing message', [
                'topic_id' => $topic->id,
                'user_id' => $userMessage->user_id,
                'input_length' => strlen($userContent),
            ]);
        }

        // Handle confirmation state if active
        if ($topic->confirmation_action) {
            $response = $this->handleConfirmationState($topic, $trimmedInput);
            if ($response !== null) {
                return $response;
            }
        }

        // Check for intent: Add Product
        if ($this->intentService->isAddProductIntent($userContent)) {
            return $this->handleAddProductIntent($topic, $userContent);
        }

        // Check for intent: Restock Product
        if ($this->intentService->isRestockProductIntent($userContent)) {
            return $this->handleRestockIntent($topic, $userContent);
        }

        // No specific intent found - forward to Gemini AI
        return $this->handleDefaultIntent($topic, $userContent);
    }

    /**
     * Handle confirmation state (when waiting for user YES/NO)
     *
     * @param ChatTopic $topic The chat topic
     * @param string $input User's trimmed input
     * @return string|null Response text or null if no confirmation match
     */
    private function handleConfirmationState(ChatTopic $topic, string $input): ?string
    {
        $action = $topic->confirmation_action;
        $payload = json_decode($topic->confirmation_payload ?? '{}', true) ?: [];

        // Handle product choice (numeric selection)
        if ($action === 'choose_restock_product') {
            return $this->handleProductChoice($topic, $input, $payload);
        }

        // Handle YES confirmation
        if ($this->intentService->isConfirmation($input)) {
            return $this->executeConfirmedAction($topic, $action, $payload);
        }

        // Handle NO confirmation
        if ($this->intentService->isRejection($input)) {
            $result = $this->confirmationService->handleCancellation($topic);
            return $result['message'];
        }

        // Invalid confirmation response
        return "Silakan jawab dengan *YA* atau *TIDAK* untuk melanjutkan.";
    }

    /**
     * Handle product choice when multiple candidates exist
     *
     * @param ChatTopic $topic The chat topic
     * @param string $input User's input (should be numeric)
     * @param array $payload Payload containing candidates
     * @return string Response text
     */
    private function handleProductChoice(ChatTopic $topic, string $input, array $payload): string
    {
        if (!$this->intentService->isNumericSelection($input)) {
            if ($this->intentService->isCancellation($input)) {
                $topic->update([
                    'confirmation_action' => null,
                    'confirmation_payload' => null,
                ]);
                return "Pemilihan produk dibatalkan.";
            }

            return "Silakan pilih produk dengan membalas angka (misal: 1), atau ketik *batal* untuk membatalkan.";
        }

        $candidates = $payload['candidates'] ?? [];
        $amount = $payload['amount'] ?? null;
        $index = $this->intentService->getSelectionIndex($input);

        if (!isset($candidates[$index])) {
            if (!app()->environment('production')) {
                Log::warning('ChatbotMessageService: Invalid product choice', [
                    'index' => $index,
                    'candidate_count' => count($candidates),
                ]);
            }

            return "Nomor yang dipilih tidak valid. Silakan pilih angka antara 1 sampai " . count($candidates) . ", atau ketik *batal*.";
        }

        // Get selected product
        $chosen = $candidates[$index];
        $product = $this->productService->getProductById($chosen['id']);

        if (!$product) {
            if (!app()->environment('production')) {
                Log::warning('ChatbotMessageService: Selected product not found', [
                    'product_id' => $chosen['id'] ?? null,
                ]);
            }

            $topic->update([
                'confirmation_action' => null,
                'confirmation_payload' => null,
            ]);

            return "Produk yang dipilih sudah tidak tersedia di database. Silakan ulangi perintah restock.";
        }

        // Move to restock confirmation stage
        $topic->update([
            'confirmation_action' => 'restock_product',
            'confirmation_payload' => json_encode([
                'product_id' => $product->id,
                'qty' => (int)$amount,
            ]),
        ]);

        return "Anda memilih produk: {$product->name}.\n\n" .
            "Konfirmasi restock berikut:\n" .
            "- Produk : {$product->name}\n" .
            "- Jumlah : {$amount}\n\n" .
            "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";
    }

    /**
     * Execute confirmed action (add product or restock)
     *
     * @param ChatTopic $topic The chat topic
     * @param string $action The action to execute
     * @param array $payload Action payload
     * @return string Response text
     */
    private function executeConfirmedAction(ChatTopic $topic, string $action, array $payload): string
    {
        if (!app()->environment('production')) {
            Log::info('ChatbotMessageService: Executing confirmed action', [
                'action' => $action,
                'topic_id' => $topic->id,
            ]);
        }

        if ($action === 'add_product') {
            $result = $this->confirmationService->handleAddProduct($topic, $payload);
        } elseif ($action === 'restock_product') {
            $result = $this->confirmationService->handleRestockProduct($topic, $payload);
        } else {
            if (!app()->environment('production')) {
                Log::warning('ChatbotMessageService: Unknown confirmation action', ['action' => $action]);
            }
            $result = ['success' => false, 'message' => "Aksi tidak dikenali."];
        }

        return $result['message'];
    }

    /**
     * Handle add product intent
     *
     * @param ChatTopic $topic The chat topic
     * @param string $userContent User's message
     * @return string Response text
     */
    private function handleAddProductIntent(ChatTopic $topic, string $userContent): string
    {
        if (!app()->environment('production')) {
            Log::info('ChatbotMessageService: Handling add product intent', ['topic_id' => $topic->id]);
        }

        $productData = $this->intentService->extractProductData($userContent);

        // Set confirmation state
        $topic->update([
            'confirmation_action' => 'add_product',
            'confirmation_payload' => json_encode($productData),
        ]);

        return "Konfirmasi penambahan produk:\n" .
            "- Nama: {$productData['name']}\n" .
            "- Harga: {$productData['price']}\n" .
            "- Stok: {$productData['current_stock']}\n" .
            "- Deskripsi: {$productData['description']}\n\n" .
            "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";
    }

    /**
     * Handle restock product intent
     *
     * @param ChatTopic $topic The chat topic
     * @param string $userContent User's message
     * @return string Response text
     */
    private function handleRestockIntent(ChatTopic $topic, string $userContent): string
    {
        if (!app()->environment('production')) {
            Log::info('ChatbotMessageService: Handling restock intent', ['topic_id' => $topic->id]);
        }

        // Extract restock amount
        $amount = $this->intentService->extractRestockAmount($userContent);

        if (!$amount) {
            return "Jumlah stok tidak ditemukan. Contoh: `restock produk indomie goreng stok 50`";
        }

        // Extract product name
        $productName = $this->intentService->extractProductNameFromRestock($userContent);

        // Find product candidates
        $candidates = $this->productService->findCandidates($productName, 5);

        if (empty($candidates)) {
            if (!app()->environment('production')) {
                Log::info('ChatbotMessageService: No products found for restock', ['product_name' => $productName]);
            }
            return "Produk '{$productName}' tidak ditemukan.";
        }

        // If only one candidate found, ask for confirmation directly
        if (count($candidates) === 1) {
            $product = $candidates[0]['product'];

            $topic->update([
                'confirmation_action' => 'restock_product',
                'confirmation_payload' => json_encode([
                    'product_id' => $product->id,
                    'qty' => (int)$amount,
                ]),
            ]);

            return "Konfirmasi restock stok:\n" .
                "- Produk: {$product->name}\n" .
                "- Jumlah: {$amount}\n\n" .
                "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";
        }

        // Multiple candidates - ask user to choose
        $listText = $this->productService->formatCandidatesForDisplay($candidates);

        $topic->update([
            'confirmation_action' => 'choose_restock_product',
            'confirmation_payload' => json_encode([
                'amount' => (int)$amount,
                'candidates' => $this->productService->serializeCandidates($candidates),
            ]),
        ]);

        return "Ditemukan beberapa produk yang mirip dengan '{$productName}':\n\n" .
            $listText . "\n\n" .
            "Balas dengan angka (misal: *1*) untuk memilih produk, atau ketik *batal* untuk membatalkan.";
    }

    /**
     * Handle default intent (forward to Gemini AI)
     *
     * @param ChatTopic $topic The chat topic
     * @param string $userContent User's message
     * @return string Response text (AI response or error)
     */
    private function handleDefaultIntent(ChatTopic $topic, string $userContent): string
    {
        if (!app()->environment('production')) {
            Log::info('ChatbotMessageService: Forwarding to Gemini AI', ['topic_id' => $topic->id]);
        }

        try {
            return $this->geminiService->ask($topic, $userContent);
        } catch (\Throwable $exception) {
            Log::error('ChatbotMessageService: Gemini AI request failed', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
                'topic_id' => $topic->id,
            ]);

            return "Maaf, layanan AI sedang tidak bisa dihubungi. Silakan coba lagi nanti.";
        }
    }
}
