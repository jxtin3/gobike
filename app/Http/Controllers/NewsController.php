<?php

namespace App\Http\Controllers;

use App\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9);

        return view('join-the-movement.news', compact('news'));
    }

    public function show(string $slug)
    {
        $article = News::published()->where('slug', $slug)->firstOrFail();

        $more = News::published()
            ->whereKeyNot($article->getKey())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('join-the-movement.news-show', compact('article', 'more'));
    }
}
