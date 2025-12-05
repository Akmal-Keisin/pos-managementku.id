<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\ChatTopic;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class ChatbotMessageStoreController extends Controller
{
    public function __invoke(Request $request, ChatTopic $topic)
    {
        if ($topic->user_id !== $request->user()->id) abort(403);

        $request->validate(['content' => ['required', 'string']]);
        $question = strtolower($request->content);

        // SAVE USER MESSAGE
        $userMessage = ChatMessage::create([
            'chat_topic_id' => $topic->id,
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => $request->content,
        ]);
        $topic->update(['last_message_at' => now()]);

        $trimmed = trim($question);

        // ============================================================
        // 0. HANDLE STATE KONFIRMASI / PEMILIHAN PRODUK
        // ============================================================
        if ($topic->confirmation_action) {

            // 0.a PEMILIHAN PRODUK UNTUK RESTOCK (user balas angka)
            if ($topic->confirmation_action === 'choose_restock_product') {
                $payload = json_decode($topic->confirmation_payload ?? '{}', true) ?: [];
                $candidates = $payload['candidates'] ?? [];
                $amount = $payload['amount'] ?? null;

                // User memilih nomor
                if (preg_match('/^\d+$/', $trimmed)) {
                    $index = (int)$trimmed - 1;
                    if (!isset($candidates[$index])) {
                        return $this->respond(
                            $topic,
                            $userMessage,
                            "Nomor yang dipilih tidak valid. Silakan pilih angka antara 1 sampai " . count($candidates) . ", atau ketik *batal*."
                        );
                    }

                    $chosen = $candidates[$index]; // ['id' => ..., 'name' => ...]
                    $product = Product::find($chosen['id']);
                    if (!$product) {
                        // produk sudah hilang dari DB
                        $topic->update([
                            'confirmation_action' => null,
                            'confirmation_payload' => null,
                        ]);
                        return $this->respond(
                            $topic,
                            $userMessage,
                            "Produk yang dipilih sudah tidak tersedia di database. Silakan ulangi perintah restock."
                        );
                    }

                    // Pindah ke tahap konfirmasi restock normal (YA/TIDAK)
                    $topic->update([
                        'confirmation_action'  => 'restock_product',
                        'confirmation_payload' => json_encode([
                            'product_id' => $product->id,
                            'qty'        => (int)$amount,
                        ]),
                    ]);

                    $text = "Anda memilih produk: {$product->name}.\n\n" .
                        "Konfirmasi restock berikut:\n" .
                        "- Produk : {$product->name}\n" .
                        "- Jumlah : {$amount}\n\n" .
                        "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";

                    return $this->respond($topic, $userMessage, $text);
                }

                // User membatalkan
                if (in_array($trimmed, ['batal', 'cancel', 'stop'])) {
                    $topic->update([
                        'confirmation_action'  => null,
                        'confirmation_payload' => null,
                    ]);
                    return $this->respond($topic, $userMessage, "Pemilihan produk dibatalkan.");
                }

                // Jawaban lain → ingatkan cara memilih
                return $this->respond(
                    $topic,
                    $userMessage,
                    "Silakan pilih produk dengan membalas angka (misal: 1), atau ketik *batal* untuk membatalkan."
                );
            }

            // 0.b KONFIRMASI YA / TIDAK UNTUK AKSI YANG LAIN
            if (in_array($trimmed, ['ya', 'iya', 'yes', 'y'])) {
                return $this->handleConfirmedAction($topic, $userMessage);
            }

            if ($trimmed === ['tidak', 'no', 'n']) {
                $topic->update([
                    'confirmation_action'  => null,
                    'confirmation_payload' => null,
                ]);
                return $this->respond($topic, $userMessage, "Aksi dibatalkan.");
            }
        }

        // ============================================================
        // A. TAMBAH PRODUK — BOT MINTA KONFIRMASI
        // ============================================================
        if (
			preg_match('/tambah.*produk/i', $question) ||
			preg_match('/add.*product/i', $question) ||
			preg_match('/create.*product/i', $question)
		) {

            preg_match('/(?:nama|name)\s+([a-zA-Z0-9 ]+?)(?=\s+(harga|price|stok|stock|deskripsi|description)|$)/i', $question, $name);
            preg_match('/(?:harga|price)\s+([0-9]+)/i', $question, $price);
            preg_match('/(?:stok|stock)\s+([0-9]+)/i', $question, $stock);
            preg_match('/(?:deskripsi|description)\s+(.+)$/i', $question, $desc);

            $payload = [
                'name'          => $name[1] ?? null,
                'price'         => $price[1] ?? 0,
                'current_stock' => $stock[1] ?? 0,
                'description'   => $desc[1] ?? '',
            ];

            $topic->update([
                'confirmation_action'  => 'add_product',
                'confirmation_payload' => json_encode($payload),
            ]);

            $text = "Konfirmasi penambahan produk:\n" .
                "- Nama: {$payload['name']}\n" .
                "- Harga: {$payload['price']}\n" .
                "- Stok: {$payload['current_stock']}\n" .
                "- Deskripsi: {$payload['description']}\n\n" .
                "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";

            return $this->respond($topic, $userMessage, $text);
        }

        // ============================================================
        // B. UPDATE / RESTOCK PRODUK — MINTA PILIHAN + KONFIRMASI
        // ============================================================
        if (
			preg_match('/(restok|restock).*produk/i', $question) ||
			preg_match('/(restock|update|increase).*stock/i', $question)
		) {
            // Ambil jumlah stok
            preg_match('/(?:stok|stock)\s+(\d+)/i', $question, $stokMatches);
            $amount = $stokMatches[1] ?? null;

            if (!$amount) {
                return $this->respond($topic, $userMessage, "Jumlah stok tidak ditemukan. Contoh: `restock produk indomie goreng stok 50`");
            }

            // Ambil nama produk (hapus angka & kata restok/restock)
            $productName = preg_replace('/[0-9]+/', '', $question);
			$productName = str_ireplace(
				['restok produk', 'restock product', 'update stock', 'increase stock'],
				'',
				$productName
			);
			$productName = trim($productName);

            // Cari kandidat produk
            $candidates = $this->findProductCandidates($productName, 5);

            if (empty($candidates)) {
                return $this->respond($topic, $userMessage, "Produk '{$productName}' tidak ditemukan.");
            }

            // Hanya 1 kandidat → langsung ke tahap konfirmasi YA/TIDAK
            if (count($candidates) === 1) {
                $product = $candidates[0]['product'];

                $topic->update([
                    'confirmation_action'  => 'restock_product',
                    'confirmation_payload' => json_encode([
                        'product_id' => $product->id,
                        'qty'        => (int)$amount,
                    ]),
                ]);

                $text = "Konfirmasi restock stok:\n" .
                    "- Produk: {$product->name}\n" .
                    "- Jumlah: {$amount}\n\n" .
                    "Balas *YA* untuk melanjutkan atau *TIDAK* untuk membatalkan.";

                return $this->respond($topic, $userMessage, $text);
            }

            // > 1 kandidat → minta user pilih nomor
            $listText = collect($candidates)->map(function ($c, $idx) {
				$p = $c['product'];
				$no = $idx + 1;
				return "[{$no}] {$p->name}" .
					(isset($p->price) ? " — Harga: {$p->price}" : "") .
					(isset($p->current_stock) ? " — Stok: {$p->current_stock}" : "");
			})->implode("\n");


            $topic->update([
                'confirmation_action'  => 'choose_restock_product',
                'confirmation_payload' => json_encode([
                    'amount'     => (int)$amount,
                    'candidates' => array_map(function ($c) {
                        return [
                            'id'   => $c['product']->id,
                            'name' => $c['product']->name,
                        ];
                    }, $candidates),
                ]),
            ]);

            $text = "Ditemukan beberapa produk yang mirip dengan '{$productName}':\n\n" .
                $listText . "\n\n" .
                "Balas dengan angka (misal: *1*) untuk memilih produk, atau ketik *batal* untuk membatalkan.";

            return $this->respond($topic, $userMessage, $text);
        }

        // ============================================================
        // Z. Query lain → teruskan ke Gemini
        // ============================================================
        try {
            $assistant = $this->askGemini($topic, $request->content);
        } catch (\Throwable $e) {
            $assistant = "Maaf, layanan AI sedang tidak bisa dihubungi.";
        }

        return $this->respond($topic, $userMessage, $assistant);
    }

    // =====================================================================
    // HANDLE KONFIRMASI YA
    // =====================================================================
    private function handleConfirmedAction(ChatTopic $topic, ChatMessage $userMessage)
    {
        $action  = $topic->confirmation_action;
        $payload = json_decode($topic->confirmation_payload ?? '{}', true) ?: [];

        if ($action === 'add_product') {
            Product::create($payload);
            $topic->update([
                'confirmation_action'  => null,
                'confirmation_payload' => null,
            ]);

            return $this->respond($topic, $userMessage, "Produk berhasil ditambahkan!");
        }

        if ($action === 'restock_product') {
            $product = Product::find($payload['product_id'] ?? null);

            if (!$product) {
                $topic->update([
                    'confirmation_action'  => null,
                    'confirmation_payload' => null,
                ]);
                return $this->respond($topic, $userMessage, "Produk tidak ditemukan saat proses restock. Silakan ulangi.");
            }

            $qty = (int)($payload['qty'] ?? 0);
            $product->current_stock += $qty;
            $product->save();

            $topic->update([
                'confirmation_action'  => null,
                'confirmation_payload' => null,
            ]);

            return $this->respond(
                $topic,
                $userMessage,
                "Stok untuk produk '{$product->name}' berhasil ditambah sebesar {$qty}."
            );
        }

        return $this->respond($topic, $userMessage, "Aksi tidak dikenali.");
    }

    // =====================================================================
    // Gemini API
    // =====================================================================
    private function askGemini(ChatTopic $topic, string $userText): string
    {
        $apiKey = config('services.gemini.key');
        $model  = config('services.gemini.model');

        $history = ChatMessage::where('chat_topic_id', $topic->id)
            ->orderBy('created_at')
            ->limit(15)
            ->get(['role', 'content'])
            ->map(fn ($m) => strtoupper($m->role) . ": " . $m->content)
            ->implode("\n\n");

        $prompt = $history . "\n\nUSER: " . $userText;

        $resp = Http::post(
            "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]
        );

        return $resp->json()['candidates'][0]['content']['parts'][0]['text']
            ?? "(Tidak ada respon dari AI)";
    }

    // =====================================================================
    // Response JSON
    // =====================================================================
    private function respond($topic, $userMessage, string $assistantContent)
    {
        $assistantMessage = ChatMessage::create([
            'chat_topic_id' => $topic->id,
            'role'          => 'assistant',
            'content'       => $assistantContent,
        ]);

        return response()->json([
            'user_message'      => $userMessage,
            'assistant_message' => $assistantMessage,
            'topic_id'          => $topic->id,
        ], 201);
    }

    // =====================================================================
    // Cari kandidat produk berdasarkan nama (fuzzy, pakai skor)
    // =====================================================================
    private function findProductCandidates(string $input, int $max = 5): array
    {
        $input = strtolower(trim($input));

        $products = Product::all();
        if ($products->isEmpty()) {
            return [];
        }

        // Normalisasi input → ambil kata-kata penting
        $normalized = preg_replace('/[^a-z0-9 ]/i', ' ', $input);
        $words      = array_filter(explode(' ', $normalized));
        $words      = array_filter($words, fn ($w) => strlen($w) >= 3);

        if (empty($words)) {
            return [];
        }

        $candidates = [];

        foreach ($products as $p) {
            $name  = strtolower($p->name);
            $score = 0;

            foreach ($words as $w) {
                if (str_contains($name, $w)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $candidates[] = [
                    'product' => $p,
                    'score'   => $score,
                ];
            }
        }

        if (empty($candidates)) {
            return [];
        }

        // Urutkan dari skor tertinggi
        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($a['product']->name, $b['product']->name);
            }
            return $b['score'] <=> $a['score'];
        });

        return array_slice($candidates, 0, $max);
    }
}
