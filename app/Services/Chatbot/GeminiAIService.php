<?php

namespace App\Services\Chatbot;

use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiAIService
 *
 * Responsible for communicating with Google Gemini API
 * Sends messages and conversation history for AI-powered responses
 */
class GeminiAIService
{
    private string $apiKey;
    private string $model;
    private int $historyLimit = 15;

    /**
     * Initialize the service with API credentials
     */
    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model');
    }

    /**
     * Ask Gemini AI a question based on conversation history
     *
     * @param ChatTopic $topic The chat topic containing conversation history
     * @param string $userText The user's message
     * @return string AI response text
     * @throws \Exception If API call fails
     */
    public function ask(ChatTopic $topic, string $userText): string
    {
        try {
            // Build conversation history with context
            $prompt = $this->buildPrompt($topic, $userText);

            if (!app()->environment('production')) {
                Log::debug('GeminiAIService: Sending request to Gemini API', [
                    'model' => $this->model,
                    'history_limit' => $this->historyLimit,
                ]);
            }

            // Call Gemini API
            $response = $this->callGeminiAPI($prompt);

            // Extract response text
            $responseText = $response['candidates'][0]['content']['parts'][0]['text']
                ?? "(No response from AI)";

            if (!app()->environment('production')) {
                Log::info('GeminiAIService: Received response from Gemini', [
                    'response_length' => strlen($responseText),
                ]);
            }

            return $responseText;
        } catch (\Throwable $exception) {
            Log::error('GeminiAIService: API call failed', [
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Build prompt from conversation history and user message
     *
     * @param ChatTopic $topic The chat topic
     * @param string $userText The current user message
     * @return string Complete prompt for Gemini
     */
    private function buildPrompt(ChatTopic $topic, string $userText): string
    {
        // Fetch recent conversation history
        $history = ChatMessage::where('chat_topic_id', $topic->id)
            ->orderBy('created_at')
            ->limit($this->historyLimit)
            ->get(['role', 'content'])
            ->map(fn($message) => strtoupper($message->role) . ": " . $message->content)
            ->implode("\n\n");

        // Build complete prompt
        $prompt = $history . "\n\nUSER: " . $userText;

        if (!app()->environment('production')) {
            Log::debug('GeminiAIService: Built prompt for API', [
                'prompt_length' => strlen($prompt),
                'message_count' => substr_count($history, "\n\n") + 1,
            ]);
        }

        return $prompt;
    }

    /**
     * Call Gemini API with retry logic
     *
     * @param string $prompt The prompt to send
     * @return array API response as array
     * @throws \Exception If API call fails after retries
     */
    private function callGeminiAPI(string $prompt): array
    {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('GeminiAIService: API returned error status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);

                throw new \Exception("Gemini API error: {$response->status()}");
            }

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('GeminiAIService: HTTP request failed', [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Set custom history limit (for testing or specific use cases)
     */
    public function setHistoryLimit(int $limit): self
    {
        $this->historyLimit = max(1, min($limit, 50));
        return $this;
    }
}
