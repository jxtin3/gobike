<?php

use Illuminate\Support\Facades\Http;

function fakeGemini(): void
{
    config([
        'chatbot.gemini_api_key' => 'test-api-key',
        'chatbot.model' => 'test-model',
        'chatbot.system_prompt' => 'Test prompt.',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Hello!']]]]],
        ]),
    ]);
}

function chatHistory(int $turns): array
{
    return collect(range(1, $turns))
        ->map(fn ($i) => ['role' => $i % 2 ? 'user' : 'model', 'text' => "turn {$i}"])
        ->all();
}

it('rejects an oversized chat history', function () {
    fakeGemini();

    $this->postJson('/chatbot/message', ['message' => 'Hi', 'history' => chatHistory(13)])
        ->assertJsonValidationErrors('history');
});

it('accepts the normal chat history the website sends', function () {
    fakeGemini();

    $this->postJson('/chatbot/message', ['message' => 'Hi', 'history' => chatHistory(12)])
        ->assertOk()
        ->assertJson(['reply' => 'Hello!']);
});

it('limits chatbot requests per minute', function () {
    fakeGemini();

    for ($i = 0; $i < 20; $i++) {
        $this->postJson('/chatbot/message', ['message' => 'Hi'])->assertOk();
    }

    $this->postJson('/chatbot/message', ['message' => 'Hi'])->assertStatus(429);
});