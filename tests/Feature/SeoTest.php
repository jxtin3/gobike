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
    $graph = collect(json_decode($match[1], true)['@graph'])->keyBy('@type');

    // The website's brand is OneGoBike; the organization's official name is Go Bike Project.
    expect($graph['WebSite'])
        ->toMatchArray(['name' => 'OneGoBike', 'url' => url('/')])
        ->and($graph['WebSite']['alternateName'])->toContain('Go Bike Project', 'GoBike', 'Go Bike')
        ->and($graph['NGO'])->toMatchArray(['name' => 'Go Bike Project', 'foundingDate' => '2019-03-16'])
        ->and($graph['NGO']['alternateName'])->toContain('GoBike', 'Go Bike', 'OneGoBike')
        ->and($graph['NGO']['founder'][0]['name'])->toBe('Edren M. Llanillo')
        ->and($graph['WebSite']['publisher']['@id'])->toBe($graph['NGO']['@id']);
});

it('names the website OneGoBike and the organization Go Bike Project (GoBike)', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>OneGoBike | Go Bike Project (GoBike) – Youth Responders in Pangasinan</title>', false)
        ->assertSee('<meta property="og:site_name"   content="OneGoBike" />', false)
        ->assertSee('The Go Bike Project (GoBike) trains youth volunteers in Bugallon, Pangasinan', false);

    $this->get('/about')->assertSee('<title>About Us | OneGoBike</title>', false);
    $this->get(route('terms'))->assertSee('<title>Terms &amp; Conditions | OneGoBike</title>', false);
});

it('names the Go Bike Project (GoBike) in the footer of every page', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertSee('The Go Bike Project (GoBike) empowers communities')
        ->assertSee('&copy; '.date('Y').' Go Bike Project. All rights reserved.', false);
})->with(['/', '/about', '/contact']);

it('adds the Google Search Console tag only when it is configured', function () {
    $this->get('/')->assertDontSee('google-site-verification', false);

    config(['services.google.site_verification' => 'abc123-token']);

    $this->get('/')->assertSee('<meta name="google-site-verification" content="abc123-token" />', false);
});

// ---------------------------------------------------------------- one address

it('sends the www. address to gobikeproject.org, keeping the page and query', function () {
    $this->get('https://www.gobikeproject.org/about?utm_source=facebook')
        ->assertStatus(301)
        ->assertRedirect('https://gobikeproject.org/about?utm_source=facebook');
});

it('sends the old Railway address to gobikeproject.org', function () {
    $this->get('https://gobike.up.railway.app/')
        ->assertStatus(301)
        ->assertRedirect('https://gobikeproject.org/');
});

it('keeps the method when an old app posts to the Railway address', function () {
    $this->postJson('https://gobike.up.railway.app/api/mobile/login', ['email' => 'a@b.c'])
        ->assertStatus(308)
        ->assertRedirect('https://gobikeproject.org/api/mobile/login');
});

it('leaves the health check alone on any address', function () {
    $this->get('https://gobike.up.railway.app/up')->assertOk();
});

it('leaves the site\'s own address alone', function () {
    $this->get('https://gobikeproject.org/about')->assertOk();
    $this->get('/about')->assertOk();
});

it('points search engines to the sitemap and keeps private pages out', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://gobikeproject.org/sitemap.xml', 'Disallow: /admin', 'Disallow: /donate/')
        ->not->toContain("Disallow: /donate\n");
});
