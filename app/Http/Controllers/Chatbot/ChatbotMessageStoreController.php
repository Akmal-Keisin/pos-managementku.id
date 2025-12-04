<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Services\DBToolService;

class ChatbotMessageStoreController extends Controller
{
	/**
	 * Store a new user message (and a placeholder assistant reply).
	 */
	public function __invoke(Request $request, ChatTopic $topic)
	{
		// Temporary debug logging to capture AJAX/fetch failures
		try {
			Log::debug('ChatbotMessageStoreController::__invoke request', [
				'user_id' => $request->user()?->id,
				'topic_id' => $topic->id,
				'headers' => [
					'X-Inertia' => $request->header('X-Inertia'),
					'X-Requested-With' => $request->header('X-Requested-With'),
					'Accept' => $request->header('Accept'),
				],
				'wantsJson' => $request->wantsJson(),
				'isAjax' => $request->ajax(),
				'body' => $request->all(),
			]);
		} catch (\Throwable $e) {
			// swallow logging errors
		}
		if ($topic->user_id !== $request->user()->id) {
			abort(403);
		}

		$request->validate([
			'content' => ['required', 'string'],
		]);

		$userMessage = ChatMessage::create([
			'chat_topic_id' => $topic->id,
			'user_id' => $request->user()->id,
			'role' => 'user',
			'content' => $request->input('content'),
		]);

		// Update topic's last_message_at immediately so UI shows activity
		$topic->update(['last_message_at' => now()]);

		$assistantMessage = null;

		// Check DB first for direct answers (stock/price). This avoids calling the LLM
		// when we can answer from authoritative data.
		try {
			$dbTool = new DBToolService();
			$rows = $dbTool->searchProducts($request->input('content'));
			$lines = $dbTool->formatProductLines($rows);
			$this->lastProductData = $lines;
			$asked = strtolower($request->input('content'));
			$isStockOrPriceQuery = preg_match('/\b(stok|stock|stoknya|harga|price)\b/i', $asked);

			if ($isStockOrPriceQuery && !empty($lines)) {
				// Build a deterministic assistant reply from DB rows
				$assistantLines = array_map(fn($l) => $l, $lines);
				$assistantContent = "Berikut data produk yang sesuai dengan permintaan Anda:\n" . implode("\n", $assistantLines);

				try {
					$assistantMessage = ChatMessage::create([
						'chat_topic_id' => $topic->id,
						'role' => 'assistant',
						'content' => $assistantContent,
					]);
					$topic->update(['last_message_at' => now()]);
				} catch (\Throwable $e) {
					Log::error('Failed to save direct DB assistant message: ' . $e->getMessage(), ['topic' => $topic->id]);
				}

				$responsePayload = [
					'user_message' => $userMessage,
					'assistant_message' => $assistantMessage,
					'topic_id' => $topic->id,
					'debug_db' => $this->lastProductData ?? [],
				];

				try {
					Log::debug('ChatbotMessageStoreController::direct-db-response', ['topic' => $topic->id, 'rows' => count($rows)]);
				} catch (\Throwable $e) {}

				return response()->json($responsePayload, 201);
			}
		} catch (\Throwable $e) {
			Log::warning('DBToolService (direct) failed: ' . $e->getMessage(), ['topic' => $topic->id]);
		}

		// Call the configured model to generate an assistant reply and persist it.
		try {
			$assistantContent = $this->generateAssistantResponse($topic, $request->input('content'));
		} catch (\Throwable $e) {
			Log::error('AI generation failed: ' . $e->getMessage(), ['topic' => $topic->id]);
			$assistantContent = "Sorry, I couldn't reach the AI service right now. Please try again later.";
		}

		try {
			$assistantMessage = ChatMessage::create([
				'chat_topic_id' => $topic->id,
				'role' => 'assistant',
				'content' => $assistantContent,
			]);

			$topic->update(['last_message_at' => now()]);
		} catch (\Throwable $e) {
			Log::error('Failed to save assistant message: ' . $e->getMessage(), ['topic' => $topic->id]);
			// don't throw — user message already persisted
		}

		// Log saved message ids so we can verify persistence during debugging
		try {
			Log::debug('ChatbotMessageStoreController::saved', [
				'user_message_id' => $userMessage?->id,
				'assistant_message_id' => $assistantMessage?->id,
				'topic_id' => $topic->id,
			]);
		} catch (\Throwable $e) {
			// ignore logging errors
		}

		$responsePayload = [
			'user_message' => $userMessage,
			'assistant_message' => $assistantMessage,
			'topic_id' => $topic->id,
			'debug_db' => $this->lastProductData ?? [],
		];

		// Record the outgoing JSON payload and status so we can correlate with client behaviour
		try {
			Log::debug('ChatbotMessageStoreController::response', [
				'payload' => $responsePayload,
				'status' => 201,
				'topic' => $topic->id,
			]);
		} catch (\Throwable $e) {
			// ignore logging errors
		}

		return response()->json($responsePayload, 201);


		// Fallback: normal redirect for browser form submissions
		return redirect()->route('chatbot.index', ['topic' => $topic->id]);
	}

	/**
	 * Generate assistant response by calling Gemini (Google Generative Language API).
	 * This method always calls the Generative API and expects the model configured
	 * in `config('services.gemini.model')` to be accessible.
	 */
	private function generateAssistantResponse(ChatTopic $topic, string $newUserContent): string
	{
		// Use DBToolService to fetch structured product data when user asks about stock/product
		try {
			$dbTool = new DBToolService();
			$rows = $dbTool->searchProducts($newUserContent);
			$productDataLines = $dbTool->formatProductLines($rows);
			$this->lastProductData = $productDataLines;
		} catch (\Throwable $e) {
			Log::warning('DBToolService failed: ' . $e->getMessage(), ['topic' => $topic->id]);
			$productDataLines = [];
		}
		$apiKey = config('services.gemini.key');
		$model = config('services.gemini.model');

		// Prepare history retrieval helper. Prepend DB data as a clear SYSTEM block so
		// the model can use live values for factual answers.
		$getHistory = function (int $limit) use ($topic, $newUserContent) {
			$history = ChatMessage::where('chat_topic_id', $topic->id)
				->orderBy('created_at', 'asc')
				->take($limit)
				->get(['role', 'content'])
				->map(fn ($m) => strtoupper($m->role) . ": " . $m->content)
				->toArray();

			// Build parts: start with SYSTEM DB lines if available, then conversation history
			$parts = [];
			if (!empty($this->lastProductData ?? [])) {
				// Provide an explicit authoritative instruction so the model uses DB values.
				$parts[] = "SYSTEM: THE FOLLOWING INVENTORY DATA IS AUTHORITATIVE AND COMES DIRECTLY FROM THE STORE DATABASE.\nUse these values when answering; do not invent or claim lack of access.\n" . implode("\n", $this->lastProductData);
			}

			$parts[] = "USER: " . $newUserContent;
			$parts = array_merge($parts, $history);

			return implode("\n\n", $parts);
		};

		$url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

		// Attempts: first normal, second larger max tokens, third trimmed history and low temperature
		$attempts = [
			[ 'temperature' => 0.2, 'maxOutputTokens' => 512, 'historyLimit' => 20 ],
			[ 'temperature' => 0.2, 'maxOutputTokens' => 1024, 'historyLimit' => 20 ],
			[ 'temperature' => 0.0, 'maxOutputTokens' => 512, 'historyLimit' => 6 ],
		];

		foreach ($attempts as $i => $cfg) {
			$attemptNum = $i + 1;
			$prompt = $getHistory((int) $cfg['historyLimit']);

			try {
				$resp = Http::withHeaders([
					'Content-Type' => 'application/json',
				])->post($url, [
					'contents' => [
						[
							'parts' => [
								['text' => $prompt]
							]
						]
					],
					'generationConfig' => [
						'temperature' => $cfg['temperature'],
						'maxOutputTokens' => $cfg['maxOutputTokens'],
					],
				]);
			} catch (\Throwable $e) {
				Log::warning('Gemini HTTP request failed on attempt ' . $attemptNum, ['message' => $e->getMessage(), 'topic' => $topic->id]);
				$resp = null;
			}

			if (!$resp || !$resp->successful()) {
				$bodyText = $resp ? $resp->body() : '[no response]';
				Log::warning('Gemini API non-success', ['attempt' => $attemptNum, 'status' => $resp?->status(), 'body' => $bodyText, 'topic' => $topic->id]);
				// try next attempt
				continue;
			}

			$body = $resp->json();
			$extracted = $this->extractTextFromGeminiBody($body);

			if ($extracted !== null && trim($extracted) !== '') {
				Log::debug('Gemini API success', ['attempt' => $attemptNum, 'topic' => $topic->id]);
				return $extracted;
			}

			// Log response for analysis and continue retrying
			Log::debug('Gemini response (unable to extract text)', ['attempt' => $attemptNum, 'body' => $body, 'topic' => $topic->id]);
			// continue loop to retry with adjusted config
		}

		// After attempts exhausted, return friendly fallback
		Log::error('Gemini attempts exhausted without usable text', ['topic' => $topic->id]);
		return "(AI tidak mengembalikan teks yang bisa dibaca — coba lagi.)";
	}

	/**
	 * Attempt to extract human-readable text from various Gemini response shapes.
	 * Returns first non-empty string found or null when nothing usable is present.
	 */
	private function extractTextFromGeminiBody(array $body): ?string
	{
		// 1) candidates[].content.parts[].text
		if (!empty($body['candidates']) && is_array($body['candidates'])) {
			foreach ($body['candidates'] as $cand) {
				if (!empty($cand['content'])) {
					// content may be string or array with parts
					if (is_string($cand['content']) && trim($cand['content']) !== '') {
						return $cand['content'];
					}
					if (isset($cand['content']['parts']) && is_array($cand['content']['parts'])) {
						foreach ($cand['content']['parts'] as $part) {
							if (is_string($part['text'] ?? '') && trim($part['text']) !== '') {
								return $part['text'];
							}
						}
					}
				}
			}
		}

		// 2) output[].content[].text (some responses use output key)
		if (!empty($body['output']) && is_array($body['output'])) {
			foreach ($body['output'] as $out) {
				if (!empty($out['content']) && is_array($out['content'])) {
					foreach ($out['content'] as $content) {
						if (is_string($content['text'] ?? '') && trim($content['text']) !== '') {
							return $content['text'];
						}
						// some content items may have nested parts
						if (isset($content['parts']) && is_array($content['parts'])) {
							foreach ($content['parts'] as $p) {
								if (is_string($p['text'] ?? '') && trim($p['text']) !== '') {
									return $p['text'];
								}
							}
						}
					}
				}
			}
		}

		// 3) shallow scan: find first 'text' key anywhere in arrays
		$stack = [$body];
		while (!empty($stack)) {
			$node = array_pop($stack);
			if (is_array($node)) {
				foreach ($node as $k => $v) {
					if ($k === 'text' && is_string($v) && trim($v) !== '') {
						return $v;
					}
					if (is_array($v)) $stack[] = $v;
				}
			}
		}

		return null;
	}

	/**
	 * Search `products` for names/sku referenced in the user's content and return
	 * an array of short human-readable lines describing each matching product.
	 * This is intentionally simple (fuzzy LIKE search on words) to keep runtime low.
	 */
	private function fetchProductData(string $content): array
	{
		$this->lastProductData = [];
		if (!is_string($content) || trim($content) === '') return [];

		// Quick heuristic: only attempt when user mentions 'stok' or 'stock' or 'produk' keywords
		if (!preg_match('/\\b(stok|stock|produk|product|available|tersedia)\\b/i', $content)) {
			return [];
		}

		$words = preg_split('/[^\\p{L}\\p{N}]+/u', mb_strtolower($content));
		$terms = array_values(array_filter(array_map('trim', $words), fn($w) => strlen($w) >= 3));
		if (empty($terms)) return [];

		// Build a query matching any of the term fragments against name or sku
		$query = Product::query();
		$query->where(function ($q) use ($terms) {
			foreach ($terms as $t) {
				$q->orWhere('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%");
			}
		});

		$found = $query->limit(10)->get()->toArray();

		$lines = [];
		foreach ($found as $p) {
			$name = $p['name'] ?? ($p['title'] ?? 'Unknown');
			$sku = $p['sku'] ?? null;
			$stock = $p['current_stock'] ?? $p['stock'] ?? ($p['quantity'] ?? null);
			// fallback: if no current_stock, try summing stock history as a best-effort
			if ($stock === null) {
				try {
					$histSum = \App\Models\StockHistory::where('product_id', $p['id'])->sum('quantity');
					$stock = $histSum;
				} catch (\Throwable $e) {
					$stock = null;
				}
			}
			$price = $p['price'] ?? null;
			$line = "Product: {$name}";
			if ($sku) $line .= " (SKU: {$sku})";
			if ($stock !== null) $line .= ", Stock: {$stock}";
			if ($price !== null) $line .= ", Price: {$price}";
			$lines[] = $line;
		}

		$this->lastProductData = $lines;
		if (!empty($lines)) {
			Log::debug('fetchProductData matched products', ['lines' => $lines]);
		}
		return $lines;
	}

}
