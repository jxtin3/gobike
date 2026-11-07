<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Response;

/**
 * /sitemap.xml: the public pages Google should index, plus every published news article.
 * Add a new public page to PAGES so search engines find it.
 */
class SitemapController extends Controller
{
    /** Public pages, as path => priority (1.0 = most important). */
    private const PAGES = [
        '/' => '1.0',
        '/about' => '0.8',
        '/org-structure' => '0.8',
        '/what-we-do' => '0.8',
        '/what-we-do/programs/gobike' => '0.7',
        '/what-we-do/programs/alwar' => '0.7',
        '/what-we-do/programs/ronda-kalusugan' => '0.7',
        '/what-we-do/programs/padyakaalaman' => '0.7',
        '/what-we-do/programs/padyakaisipan' => '0.7',
        '/what-we-do/programs/hatake' => '0.7',
        '/volunteer' => '0.8',
        '/replicate-go-bike' => '0.7',
        '/donate' => '0.8',
        '/news' => '0.7',
        '/our-story' => '0.6',
        '/gallery' => '0.5',
        '/contact' => '0.6',
        '/privacy-policy' => '0.2',
        '/terms' => '0.2',
        '/cookie-policy' => '0.2',
    ];

    public function __invoke(): Response
    {
        $urls = collect(self::PAGES)->map(fn ($priority, $path) => [
            'loc' => url($path),
            'lastmod' => null,
            'priority' => $priority,
        ])->values();

        $news = News::published()->latest('published_at')->get(['slug', 'published_at', 'updated_at'])
            ->map(fn (News $article) => [
                'loc' => route('news.show', $article->slug),
                'lastmod' => ($article->updated_at ?? $article->published_at)?->toAtomString(),
                'priority' => '0.6',
            ]);

        return response()
            ->view('sitemap', ['urls' => $urls->concat($news)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
