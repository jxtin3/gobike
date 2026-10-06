<?php
use Illuminate\Support\Facades\Http;

// missing mess 422 (client error, bad syntax/request)
it('reject chatbot request without message',
function () {
        $response = $this->postJson('/chatbot/message', []);
        $response->assertStatus(422);
});


// valid mess 200+(succes)
it('returns a chatbot reply for a valid message', function () {
    config([
        'chatbot.gemini_api_key' => 'test-api-key',
        'chatbot.model' => 'test-model',
        'chatbot.system_prompt' => 'Eyy yow.',
    ]);
// fakes api just testing
    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '**Hello!** *How can I help?*',
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson('/chatbot/message', [
        'message' => 'Hello',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'reply' => 'Hello! How can I help?',
        ]);
});

// error handling 500(server error, internal service error || unavailable)
it('returns an error when the chatbot service fails', function () {
    $this->mock(\App\Services\ChatbotService::class, function ($mock) {
        $mock->shouldReceive('reply')
            ->once()
            ->andThrow(new \RuntimeException('Gemini is unavailable.'));
    });

    $response = $this->postJson('/chatbot/message', [
        'message' => 'Hello',
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'message' => "Sorry, I'm having a trouble responding right now. Please try again later.",
        ]);
});