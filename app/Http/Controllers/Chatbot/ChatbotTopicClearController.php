<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotTopicClearController extends Controller
{
	/**
	 * Clear all messages for a topic, keep the topic.
	 */
	public function __invoke(Request $request, ChatTopic $topic)
	{
		if ($topic->user_id !== $request->user()->id) {
			abort(403);
		}

		DB::transaction(function () use ($topic) {
			$topic->messages()->delete();
			$topic->update(['last_message_at' => null]);
		});

		return redirect()->route('chatbot.index', ['topic' => $topic->id]);
	}
}
