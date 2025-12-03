<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotViewController extends Controller
{
    /**
     * Show the chat UI with topics and messages.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Fetch topics for the current user
        $topics = ChatTopic::where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'last_message_at', 'created_at']);

        // Determine selected topic
        $selectedTopicId = $request->integer('topic');
        $selectedTopic = null;

        if ($selectedTopicId) {
            $selectedTopic = ChatTopic::where('user_id', $user->id)
                ->where('id', $selectedTopicId)
                ->first();
        }

        // If no topic selected, create one if none exists
        if (!$selectedTopic) {
            $selectedTopic = $topics->first();
            if (!$selectedTopic) {
                $selectedTopic = ChatTopic::create([
                    'user_id' => $user->id,
                    'title' => 'New Chat',
                ]);
                $topics = ChatTopic::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->get(['id', 'title', 'last_message_at', 'created_at']);
            }
        }

        // Load last 50 messages for the selected topic
        $messages = ChatMessage::where('chat_topic_id', $selectedTopic->id)
            ->orderBy('created_at')
            ->take(200)
            ->get(['id', 'role', 'content', 'created_at']);

        return Inertia::render('chatbot/Index', [
            'topics' => $topics,
            'selectedTopic' => $selectedTopic,
            'messages' => $messages,
        ]);
    }
}
