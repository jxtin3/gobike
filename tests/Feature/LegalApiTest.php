<?php

use App\Models\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives the app the same privacy policy the website shows', function () {
    $defaults = LegalDocument::defaults('privacy');

    $response = $this->getJson(route('api.legal.show', 'privacy'))
        ->assertOk()
        ->assertJsonPath('key', 'privacy')
        ->assertJsonPath('title', 'Privacy Policy')
        ->assertJsonPath('last_updated', $defaults['last_updated'])
        ->assertJsonPath('url', route('privacy-policy'))
        ->assertJsonCount(count($defaults['sections']), 'sections');

    expect($response->json('sections.0.title'))->toBe($defaults['sections'][0]['title'])
        ->and($response->json('sections.0.content'))->toBe(trim($defaults['sections'][0]['content']));
});

it('serves the terms and cookie policy without signing in', function (string $key, string $page) {
    $this->getJson(route('api.legal.show', $key))
        ->assertOk()
        ->assertJsonPath('url', route($page));
})->with([
    'terms' => ['terms', 'terms'],
    'cookies' => ['cookies', 'cookie-policy'],
]);

it('shows the latest text an admin saved', function () {
    LegalDocument::updateOrCreate(['key' => 'terms'], [
        'title' => 'Terms & Conditions',
        'last_updated' => 'December 1, 2026',
        'sections' => [['title' => '1. New', 'content' => 'Updated rules.'], ['title' => '', 'content' => '']],
    ]);

    $this->getJson(route('api.legal.show', 'terms'))
        ->assertOk()
        ->assertJsonPath('last_updated', 'December 1, 2026')
        ->assertJsonCount(1, 'sections')
        ->assertJsonPath('sections.0.content', 'Updated rules.');
});

it('turns content typed as HTML into the plain format the app reads', function () {
    LegalDocument::updateOrCreate(['key' => 'privacy'], [
        'title' => 'Privacy Policy',
        'last_updated' => 'December 1, 2026',
        'sections' => [[
            'title' => '1. Typed as HTML',
            'content' => '<p>We collect <strong>your name</strong> &amp; email. See <a href="/contact">Contact</a>.</p><ul><li>One</li><li>Two</li></ul><script>alert(1)</script>',
        ]],
    ]);

    $content = $this->getJson(route('api.legal.show', 'privacy'))->json('sections.0.content');

    expect($content)->toBe("We collect **your name** & email. See [Contact](/contact).\n\n- One\n- Two");
});

it('knows only the three legal documents', function () {
    $this->getJson('/api/legal/secrets')->assertNotFound();
});
