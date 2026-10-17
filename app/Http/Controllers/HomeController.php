<?php

namespace App\Http\Controllers;
use App\Models\ImpactStat;

class HomeController extends Controller
{
    public function index()
    {
        $impactStats = ImpactStat::where('section', 'impact')->orderBy('sort_order')->orderBy('id')->get();
        $impactHighlights = ImpactStat::where('section', 'highlight')->orderBy('sort_order')->orderBy('id')->get();
        $impactBadge = ImpactStat::where('section', 'badge')->orderBy('sort_order')->orderBy('id')->first();

        return view('home', compact('impactStats', 'impactHighlights', 'impactBadge'));
    }
}
