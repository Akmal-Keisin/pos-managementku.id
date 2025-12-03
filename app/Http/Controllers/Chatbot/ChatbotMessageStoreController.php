<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ChatbotMessageStoreController extends Controller
{
	/**
	 * Store a new user message (and a placeholder assistant reply).
	 */
	public function __invoke(Request $request, ChatTopic $topic)
	{
		if ($topic->user_id !== $request->user()->id) {
			abort(403);
		}

		$request->validate([
			'content' => ['required', 'string'],
		]);

		DB::transaction(function () use ($request, $topic) {
			ChatMessage::create([
				'chat_topic_id' => $topic->id,
				'user_id' => $request->user()->id,
				'role' => 'user',
				'content' => $request->input('content'),
			]);

			$topic->update(['last_message_at' => now()]);

			// Placeholder assistant message for UI testing
			ChatMessage::create([
				'chat_topic_id' => $topic->id,
				'role' => 'assistant',
				'content' => "Thanks! The AI agent will respond here soon.\n\nMeanwhile, you can format messages with **markdown**, lists, and `code`.",
			]);

			$topic->update(['last_message_at' => now()]);
		});

		return redirect()->route('chatbot.index', ['topic' => $topic->id]);
	}
}
