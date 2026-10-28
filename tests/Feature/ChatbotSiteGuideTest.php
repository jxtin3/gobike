<?php

use App\Models\News;
use App\Services\ChatbotSiteGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('describes every public page with its address', function () {
    $guide = app(ChatbotSiteGuide::class)->text();

    foreach ([
        '/about', '/org-structure', '/what-we-do', '/what-we-do#training-plan', '/volunteer', '/replicate-go-bike',
        '/donate', '/news', '/our-story', '/gallery', '/contact', '/privacy-policy', '/terms', '/cookie-policy',
        '/what-we-do/programs/gobike', '/what-we-do/programs/alwar', '/what-we-do/programs/ronda-kalusugan',
        '/what-we-do/programs/padyakaalaman', '/what-we-do/programs/padyakaisipan', '/what-we-do/programs/hatake',
    ] as $path) {
        expect($guide)->toContain("({$path})");
        $this->get(strtok($path, '#'))->assertOk();
    }

    // Menu names as visitors see them.
    expect($guide)->toContain('Who We Are → About Us', 'Join The Movement → News & Updates', 'Reach Us', '"Volunteer with us"', '"Get Trained"');
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
        ->toContain('Medical mission in Poblacion', '/news/medical-mission')
        ->not->toContain('Draft story');
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
            && str_contains($instruction, 'WEBSITE GUIDE')
            && str_contains($instruction, '(/volunteer)')
            && $request['generationConfig']['maxOutputTokens'] === 2048;
    });
});
