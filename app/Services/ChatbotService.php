<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotService
{
    /**
     * @param  array<int, array{role: string, text: string}>  $history  Prior turns, oldest first. role is 'user' or 'model'.
     */
    public function reply(string $message, array $history = []): string
    {
        $apiKey = config('chatbot.gemini_api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini is not configured. Add GEMINI_API_KEY to your .env file.');
        }

        $contents = collect($history)
            ->push(['role' => 'user', 'text' => $message])
            ->map(fn (array $turn) => [
                'role' => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ])
            ->all();

        $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
            ->acceptJson()
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('chatbot.model').':generateContent', [
                'contents' => $contents,
                'systemInstruction' => [
                    'parts' => [['text' => config('chatbot.system_prompt')]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 512,
                ],
            ]);

        $reply = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($reply === null) {
            Log::error('Gemini reply failed', ['response' => $response->json()]);

            $reason = $response['error']['message'] ?? 'Unexpected response from Gemini.';

            throw new RuntimeException("Gemini request failed: {$reason}");
        }

        return trim(str_replace('*', '', $reply));
    }
}