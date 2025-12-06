<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DBToolService;

class ChatbotToolController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'intent' => ['required', 'string'],
            'params' => ['sometimes', 'array'],
        ]);

        if (!config('chatbot.db_tool_enabled', false)) {
            return response()->json(['error' => 'DB tool disabled'], 403);
        }

        $intent = $request->input('intent');
        $params = $request->input('params', []);

        try {
            $tool = new DBToolService();
            $rows = $tool->runIntent($intent, $params);

            if (!app()->environment('production')) {
                Log::info('ChatbotToolController::runIntent', ['intent' => $intent, 'count' => count($rows)]);
            }

            return response()->json(['intent' => $intent, 'rows' => $rows], 200);
        } catch (\Throwable $e) {
            Log::error('ChatbotToolController error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('production') ? null : $e->getTraceAsString(),
                'intent' => $intent ?? null,
            ]);
            return response()->json(['error' => 'tool_failed'], 500);
        }
    }
}
