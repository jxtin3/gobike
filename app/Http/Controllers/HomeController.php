<?php

namespace App\Http\Controllers;

use App\Models\HomePartner;
use App\Models\ImpactStat;

class HomeController extends Controller
{
    public function index()
    {
        $featuredNews = \App\Models\News::published()
            ->whereNotNull('homepage_position')
            ->orderBy('homepage_position')
            ->get();

        $latestNews = $featuredNews->count() === 3
            ? $featuredNews
            : \App\Models\News::published()
                ->orderBy('published_at', 'desc')
                ->take(3)
                ->get();

        $partners = HomePartner::orderBy('sort_order')->orderBy('id')->get();
        $impactStats = ImpactStat::where('section', 'impact')->orderBy('sort_order')->orderBy('id')->get();
        $impactHighlights = ImpactStat::where('section', 'highlight')->orderBy('sort_order')->orderBy('id')->get();
        $impactBadge = ImpactStat::where('section', 'badge')->orderBy('sort_order')->orderBy('id')->first();

        return view('home', compact('latestNews', 'partners', 'impactStats', 'impactHighlights', 'impactBadge'));
    }
}
