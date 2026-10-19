<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ChatbotController extends Controller
{
    public function message(SendChatMessageRequest $request, ChatbotService $chatbot): JsonResponse
    {
        $validated = $request->validated();

        try {
            $reply = $chatbot->reply($validated['message'], $validated['history'] ?? []);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => "Sorry, I'm not available to respond right now. Please try again later.",
            ], 500);
        }

        return response()->json([
            'reply' => $reply,
        ]);
    }
}