<?php

use App\Models\LegalDocument;
use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps simple formatting in legal page HTML', function () {
    $html = '<p>Read our <strong>terms</strong> and <a href="/contact">contact us</a>.</p><ul><li>One</li></ul>';

    expect(SafeHtml::clean($html))->toBe($html);
});

it('strips scripts, event handlers and unsafe links from legal page HTML', function () {
    $clean = SafeHtml::clean(
        '<p onclick="steal()">Hi<script>alert(1)</script></p>'
        .'<a href="javascript:alert(1)">x</a><a href=" JaVa&#x09;script:alert(1)">y</a>'
        .'<img src=x onerror=alert(1)><style>p{}</style><iframe src="https://evil.test"></iframe>'
        .'<a href="https://example.com" target="_blank">ok</a>'
    );

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('<img')
        ->not->toContain('onerror')
        ->not->toContain('<style')
        ->not->toContain('<iframe')
        ->toContain('<p>Hi</p>')
        ->toContain('<a href="https://example.com" target="_blank" rel="noopener noreferrer">ok</a>');
});

it('shows sanitised HTML on the public legal page', function () {
    LegalDocument::updateOrCreate(['key' => 'terms'], [
        'title' => 'Terms & Conditions',
        'last_updated' => 'January 1, 2026',
        'sections' => [['title' => '1. Use', 'content' => '<p>Fine print<script>alert("x")</script></p>']],
    ]);

    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Fine print')
        ->assertDontSee('<script>alert("x")</script>', false);
});

it('sets the last updated date to today on every save', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    LegalDocument::updateOrCreate(['key' => 'privacy'], ['title' => 'Privacy Policy', 'last_updated' => 'January 1, 2020', 'sections' => []]);

    $this->actingAs($admin)
        ->putJson(route('admin.operations.legal.privacy.update'), [
            'title' => 'Privacy Policy',
            'last_updated' => 'January 1, 2020',
            'sections' => [['title' => '1. Data', 'content' => 'We keep little.']],
        ])
        ->assertOk()
        ->assertJson(['last_updated' => now()->format('F j, Y')]);

    expect(LegalDocument::where('key', 'privacy')->value('last_updated'))->toBe(now()->format('F j, Y'));
});

it('returns the validation message when a legal page cannot be saved', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->putJson(route('admin.operations.legal.terms.update'), ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('renders the editor with labelled fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.legal.terms'))
        ->assertOk()
        ->assertSee('aria-label="Document title"', false)
        ->assertSee('id="section-template"', false)
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('name="last_updated"', false);
});
