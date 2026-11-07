<?php

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the public pages and published news in the sitemap', function () {
    News::create(['title' => 'Medical mission', 'slug' => 'medical-mission', 'excerpt' => 'x', 'body' => 'x', 'is_published' => true, 'published_at' => now()->subDay()]);
    News::create(['title' => 'Draft', 'slug' => 'draft-story', 'excerpt' => 'x', 'body' => 'x', 'is_published' => false]);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());
    $locs = collect($xml->xpath('//*[local-name()="loc"]'))->map(fn ($loc) => (string) $loc);

    expect($locs)->toContain(url('/'), url('/about'), url('/volunteer'), url('/donate'), route('news.show', 'medical-mission'))
        ->not->toContain(route('news.show', 'draft-story'))
        ->and($locs->filter(fn ($loc) => str_contains($loc, '/admin') || str_contains($loc, '/login')))->toBeEmpty();
});

it('only lists sitemap pages that open', function () {
    $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());

    foreach ($xml->url as $url) {
        $this->get((string) $url->loc)->assertOk();
    }
});

it('gives every page a canonical address and the home page the organization details', function () {
    $this->get('/about?utm_source=facebook')
        ->assertSee('<link rel="canonical" href="'.url('/about').'" />', false)
        ->assertDontSee('application/ld+json', false);

    $home = $this->get('/')->assertSee('application/ld+json', false)->getContent();
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $home, $match);

    expect(json_decode($match[1], true))
        ->toMatchArray(['@type' => 'NGO', 'name' => 'Go Bike Project', 'foundingDate' => '2019-03-16'])
        ->and(json_decode($match[1], true)['founder'][0]['name'])->toBe('Edren M. Llanillo');
});

it('points search engines to the sitemap and keeps private pages out', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://gobikeproject.org/sitemap.xml', 'Disallow: /admin', 'Disallow: /donate/')
        ->not->toContain("Disallow: /donate\n");
});
