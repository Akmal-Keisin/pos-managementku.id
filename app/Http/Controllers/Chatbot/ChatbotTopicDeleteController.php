<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotTopicDeleteController extends Controller
{
	/**
	 * Delete a topic and its messages.
	 */
	public function __invoke(Request $request, ChatTopic $topic)
	{
		// Authorize ownership
		if ($topic->user_id !== $request->user()->id) {
			abort(403);
		}

		DB::transaction(function () use ($topic) {
			$topic->messages()->delete();
			$topic->delete();
		});

		return redirect()->route('chatbot.index');
	}
}
