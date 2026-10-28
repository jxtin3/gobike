<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotService
{
    public function __construct(private ChatbotSiteGuide $guide) {}

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

        $lastReason = 'Unexpected response from Gemini.';

        foreach ($this->models() as $model) {
            try {
                // The main model gets more time (it reads the whole website guide); the backup is faster.
                $response = $this->send($apiKey, $model, $contents, $model === config('chatbot.model') ? 20 : 12);
            } catch (ConnectionException $e) {
                Log::warning('Gemini connection failed', ['model' => $model, 'error' => $e->getMessage()]);
                $lastReason = 'Could not reach Gemini.';

                continue;
            }

            $reply = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($reply !== null) {
                return trim(str_replace('*', '', $reply));
            }

            Log::error('Gemini reply failed', ['model' => $model, 'response' => $response->json()]);
            $lastReason = $response['error']['message'] ?? 'Unexpected response from Gemini.';
        }

        throw new RuntimeException("Gemini request failed: {$lastReason}");
    }

    /**
     * The main model first, then the backup. Duplicates and blanks are dropped.
     *
     * @return array<int, string>
     */
    private function models(): array
    {
        return array_values(array_unique(array_filter([
            config('chatbot.model'),
            config('chatbot.fallback_model'),
        ])));
    }

    private function send(string $apiKey, string $model, array $contents, int $timeout): Response
    {
        return Http::withHeaders(['x-goog-api-key' => $apiKey])
            ->acceptJson()
            ->timeout($timeout)
            // One retry, only for "busy" answers. A hanging model skips straight to the backup.
            ->retry(
                2,
                300,
                fn ($exception) => $exception instanceof RequestException
                    && in_array($exception->response->status(), [429, 500, 502, 503, 504], true),
                throw: false,
            )
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', [
                'contents' => $contents,
                'systemInstruction' => [
                    // The rules, then what GoBot knows about the website's pages and steps.
                    'parts' => [['text' => config('chatbot.system_prompt')."\n\n".$this->guide->text()]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    // Room for a numbered list of steps (the model's own reasoning counts against this too).
                    'maxOutputTokens' => 2048,
                ],
            ]);
    }
}