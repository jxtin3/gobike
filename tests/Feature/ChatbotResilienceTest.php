<?php

use Illuminate\Support\Facades\Http;

const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

function geminiBody(string $text): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
}

beforeEach(function () {
    config([
        'chatbot.gemini_api_key' => 'test-api-key',
        'chatbot.model' => 'primary-model',
        'chatbot.fallback_model' => 'backup-model',
        'chatbot.system_prompt' => 'Test prompt.',
    ]);
});

it('retries once when the first model is briefly overloaded', function () {
    Http::fake([
        GEMINI_URL.'primary-model:generateContent' => Http::sequence()
            ->push(['error' => ['message' => 'overloaded']], 503)
            ->push(geminiBody('Hello from primary')),
    ]);

    $this->postJson('/chatbot/message', ['message' => 'Hi'])
        ->assertOk()
        ->assertJson(['reply' => 'Hello from primary']);

    Http::assertSentCount(2);
});

it('falls back to the backup model when the primary stays unavailable', function () {
    Http::fake([
        GEMINI_URL.'primary-model:generateContent' => Http::response(['error' => ['message' => 'overloaded']], 503),
        GEMINI_URL.'backup-model:generateContent' => Http::response(geminiBody('Hello from backup')),
    ]);

    $this->postJson('/chatbot/message', ['message' => 'Hi'])
        ->assertOk()
        ->assertJson(['reply' => 'Hello from backup']);
});

it('gives a clean error when every model fails', function () {
    Http::fake([
        GEMINI_URL.'*' => Http::response(['error' => ['message' => 'overloaded']], 503),
    ]);

    $response = $this->postJson('/chatbot/message', ['message' => 'Hi']);

    expect($response->status())->toBeGreaterThanOrEqual(400);
    $response->assertJsonMissingPath('reply');
});