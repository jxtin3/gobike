<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $news = News::query()->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))->latest()->paginate(10)->withQueryString();
        return view('admin.operations.news.index', compact('news'));
    }

    public function homepage()
    {
        $publishedNews = News::published()->orderByDesc('published_at')->get();
        $selectedNews = News::published()
            ->whereNotNull('homepage_position')
            ->orderBy('homepage_position')
            ->pluck('id')
            ->all();

        if (count($selectedNews) !== 3) {
            $selectedNews = $publishedNews->take(3)->pluck('id')->all();
        }

        return view('admin.operations.news.homepage', compact('publishedNews', 'selectedNews'));
    }

    public function updateHomepage(Request $request)
    {
        $data = $request->validate([
            'news' => ['required', 'array', 'size:3'],
            'news.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('news', 'id'),
            ],
        ]);

        if (News::published()->whereIn('id', $data['news'])->count() !== 3) {
            throw ValidationException::withMessages([
                'news' => 'Choose three published stories for the homepage.',
            ]);
        }

        DB::transaction(function () use ($data) {
            News::query()->update(['homepage_position' => null]);

            foreach ($data['news'] as $index => $newsId) {
                News::whereKey($newsId)->update(['homepage_position' => $index + 1]);
            }
        });

        return redirect()->route('admin.operations.news.homepage')->with('success', 'Homepage news successfully updated.');
    }

    public function create() { return view('admin.operations.news.create'); }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string'], 'image' => ['nullable', 'image', 'max:5120'], 'status' => ['required', 'in:Draft,Published'], 'published_at' => ['required_if:status,Published', 'nullable', 'date']]);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $data['is_published'] = $data['status'] === 'Published';
        $data['published_at'] = $data['is_published'] ? $data['published_at'] : null;
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('news', 'public');
        unset($data['image'], $data['status']);
        News::create($data);
        return redirect()->route('admin.operations.news.index')->with('success', 'News successfully added.');
    }

    public function show(News $news) { return view('admin.operations.news.show', compact('news')); }
    public function edit(News $news) { return view('admin.operations.news.edit', compact('news')); }

    public function update(Request $request, News $news)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string'], 'image' => ['nullable', 'image', 'max:5120'], 'status' => ['required', 'in:Draft,Published'], 'published_at' => ['required_if:status,Published', 'nullable', 'date']]);
        $data['is_published'] = $data['status'] === 'Published';
        $data['published_at'] = $data['is_published'] ? $data['published_at'] : null;
        if (! $data['is_published'] || Carbon::parse($data['published_at'])->isFuture()) {
            $data['homepage_position'] = null;
        }
        if ($request->hasFile('image')) {
            if ($news->image_path) Storage::disk('public')->delete($news->image_path);
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }
        unset($data['image'], $data['status']);
        $news->update($data);
        return redirect()->route('admin.operations.news.index')->with('success', 'News successfully updated.');
    }

    public function destroy(News $news)
    {
        if ($news->image_path) Storage::disk('public')->delete($news->image_path);
        $news->delete();
        return back()->with('success', 'News successfully deleted.');
    }
}
