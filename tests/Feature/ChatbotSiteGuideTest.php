<?php

use App\Models\News;
use App\Services\ChatbotSiteGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('describes every public page by the names visitors see, without any address or link', function () {
    $guide = app(ChatbotSiteGuide::class)->text();

    expect($guide)->toContain(
        'About Us', 'Org Structure & History', 'What We Do', 'Volunteer', 'Replicate Go Bike', 'Donate', 'News & Updates',
        'Our Story', 'Gallery Showcase', 'Reach Us', 'Privacy Policy', 'Terms', 'Cookie Policy',
        'Go Bike', 'Alwar', 'Ronda Kalusugan', 'PadyaKaalaman', 'Padyakaisipan', 'Hatake Farm',
        'Who We Are → About Us', 'Join The Movement → News & Updates', '"Volunteer with us"', '"Get Trained"', '"Access the Program Now"',
    )->not->toMatch('~https?://|www\.|\.com\b|\(/[a-z]~i');
});

it('reads prices, class sizes and fees from the code, so they stay right', function () {
    config(['volunteer.class_min' => 18, 'volunteer.class_max' => 30, 'replication.class_fee' => 45000]);

    $guide = app(ChatbotSiteGuide::class)->text();

    expect($guide)->toContain('Standard (₱1,500)', 'Advance (₱2,500)', '18 to 30 volunteers', '₱45,000')
        ->not->toContain('Basic (', 'Free (');
});

it('lists the latest published news', function () {
    News::create(['title' => 'Medical mission in Poblacion', 'slug' => 'medical-mission', 'excerpt' => 'x', 'body' => 'x', 'is_published' => true, 'published_at' => now()->subDay()]);
    News::create(['title' => 'Draft story', 'slug' => 'draft', 'excerpt' => 'x', 'body' => 'x', 'is_published' => false]);

    expect(app(ChatbotSiteGuide::class)->text())
        ->toContain('Medical mission in Poblacion')
        ->not->toContain('Draft story', '/news/medical-mission');
});

it('sends the rules and the website guide to Gemini with every question', function () {
    config(['chatbot.gemini_api_key' => 'test-api-key', 'chatbot.model' => 'test-model']);
    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '1. Open Volunteer.']]]]],
        ]),
    ]);

    $this->postJson('/chatbot/message', ['message' => 'Paano mag-volunteer?'])
        ->assertOk()
        ->assertJsonPath('reply', '1. Open Volunteer.');

    Http::assertSent(function ($request) {
        $instruction = $request['systemInstruction']['parts'][0]['text'];

        return str_contains($instruction, 'answer only in English or Tagalog')
            && str_contains($instruction, 'Guide step by step')
            && str_contains($instruction, 'Never give web addresses, URLs or links')
            && str_contains($instruction, 'Only talk about Go Bike and this website')
            && str_contains($instruction, 'bad words')
            && str_contains($instruction, 'WEBSITE GUIDE')
            && $request['generationConfig']['maxOutputTokens'] === 2048;
    });
});

function fakeGeminiReply(string $text): void
{
    config(['chatbot.gemini_api_key' => 'test-api-key', 'chatbot.model' => 'test-model']);
    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ]),
    ]);
}

it('takes any link or web address out of the reply', function () {
    fakeGeminiReply("1. Tap Join The Movement, then Gallery Showcase (/gallery).\n2. See https://gobike.up.railway.app/gallery or www.example.com.\nOpen: https://gobike.up.railway.app/gallery\nAlso facebook.com/gobikeprojectofficial. Email gobike@pydn.org.");

    $reply = $this->postJson('/chatbot/message', ['message' => 'Where are the photos?'])->assertOk()->json('reply');

    expect($reply)->not->toMatch('~https?://|www\.|facebook\.com|/gallery|Open:~i')
        ->toContain('Tap Join The Movement, then Gallery Showcase.')
        ->toContain('gobike@pydn.org');
});

it('answers bad words politely without sending them to the AI', function (string $message, string $expected) {
    fakeGeminiReply('should not be used');

    $this->postJson('/chatbot/message', ['message' => $message])
        ->assertOk()
        ->assertJsonPath('reply', fn ($reply) => str_contains($reply, $expected));

    Http::assertNothingSent();
})->with([
    'English' => ['you are a stupid bitch', "Let's keep it friendly."],
    'Tagalog' => ['putangina mo bot', 'Panatilihin nating magalang'],
    'mixed case' => ['GAGO ka ba', 'Panatilihin nating magalang'],
]);

it('does not block normal words that only contain a bad word', function () {
    fakeGeminiReply('1. Tap Donate.');

    // "assume" contains "ass", "Scunthorpe" contains a bad word: both are fine.
    $this->postJson('/chatbot/message', ['message' => 'I assume the Donate button is at the top?'])
        ->assertOk()
        ->assertJsonPath('reply', '1. Tap Donate.');
});
