<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ChatbotMessageStoreController
 *
 * Handles incoming chatbot messages from users
 * Validates authorization and delegates processing to ChatbotMessageService
 *
 * Responsibilities:
 * - Authorization check (user owns topic)
 * - Input validation
 * - Message persistence
 * - Response formatting
 */
class ChatbotMessageStoreController extends Controller
{
    private ChatbotMessageService $messageService;

    /**
     * Initialize controller with dependencies
     */
    public function __construct(ChatbotMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Handle incoming chat message
     *
     * @param Request $request HTTP request containing message content
     * @param ChatTopic $topic The chat topic for this message
     * @return \Illuminate\Http\JsonResponse JSON response with user and assistant messages
     */
    public function __invoke(Request $request, ChatTopic $topic)
    {
        // Authorization check: verify user owns this topic
        if ($topic->user_id !== $request->user()->id) {
            Log::warning('ChatbotMessageStoreController: Unauthorized access attempt', [
                'user_id' => $request->user()->id,
                'topic_id' => $topic->id,
                'topic_owner_id' => $topic->user_id,
            ]);

            abort(403, 'You do not have permission to access this topic.');
        }

        // Input validation
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        if (!app()->environment('production')) {
            Log::info('ChatbotMessageStoreController: Processing message', [
                'user_id' => $request->user()->id,
                'topic_id' => $topic->id,
                'message_length' => strlen($validated['content']),
            ]);
        }

        try {
            // Create and persist user message
            $userMessage = ChatMessage::create([
                'chat_topic_id' => $topic->id,
                'user_id' => $request->user()->id,
                'role' => 'user',
                'content' => $validated['content'],
            ]);

            // Update topic's last message timestamp
            $topic->update(['last_message_at' => now()]);

            if (!app()->environment('production')) {
                Log::debug('ChatbotMessageStoreController: User message created', [
                    'message_id' => $userMessage->id,
                    'topic_id' => $topic->id,
                ]);
            }

            // Process message and get assistant response
            $assistantContent = $this->messageService->processMessage(
                $topic,
                $userMessage,
                $validated['content']
            );

            // Create and persist assistant message
            $assistantMessage = ChatMessage::create([
                'chat_topic_id' => $topic->id,
                'role' => 'assistant',
                'content' => $assistantContent,
            ]);

            if (!app()->environment('production')) {
                Log::info('ChatbotMessageStoreController: Response generated successfully', [
                    'topic_id' => $topic->id,
                    'user_message_id' => $userMessage->id,
                    'assistant_message_id' => $assistantMessage->id,
                    'response_length' => strlen($assistantContent),
                ]);
            }

            // Return JSON response with both messages
            return response()->json([
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'topic_id' => $topic->id,
            ], 201);
        } catch (\Throwable $exception) {
            Log::error('ChatbotMessageStoreController: Unexpected error while processing message', [
                'error' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => app()->environment('production') ? null : $exception->getTraceAsString(),
                'user_id' => $request->user()->id,
                'topic_id' => $topic->id,
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your message. Please try again.',
            ], 500);
        }
    }
}
