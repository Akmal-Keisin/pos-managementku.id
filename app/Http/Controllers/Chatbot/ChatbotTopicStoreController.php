<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatbotTopicStoreController extends Controller
{
	/**
	 * Create a new chat topic.
	 */
	public function __invoke(Request $request)
	{
		$request->validate([
			'title' => ['nullable', 'string', 'max:255'],
		]);

		$topic = ChatTopic::create([
			'user_id' => $request->user()->id,
			'title' => $request->input('title') ?: 'New Chat',
		]);

		return redirect()->route('chatbot.index', ['topic' => $topic->id]);
	}
}
