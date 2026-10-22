<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $news = News::query()
            ->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.operations.news.index', compact('news'));
    }

    public function create()
    {
        return view('admin.operations.news.create');
    }

    public function store(Request $request)
    {
        $data = $this->payload($request);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', Media::disk());
        }

        News::create($data);

        return redirect()->route('admin.operations.news.index')->with('success', 'News post added.');
    }

    public function show(News $news)
    {
        return view('admin.operations.news.show', compact('news'));
    }

    public function edit(News $news)
    {
        return view('admin.operations.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $data = $this->payload($request);

        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $news->image_path;
            $data['image_path'] = $request->file('image')->store('news', Media::disk());
        }

        $news->update($data);

        // Only remove the old image once the new one is stored and saved.
        if ($oldImage) {
            Storage::disk(Media::disk())->delete($oldImage);
        }

        return redirect()->route('admin.operations.news.index')->with('success', 'News post updated.');
    }

    public function destroy(News $news)
    {
        if ($news->image_path) {
            Storage::disk(Media::disk())->delete($news->image_path);
        }
        $news->delete();

        return redirect()->route('admin.operations.news.index')->with('success', 'News post deleted.');
    }

    /**
     * Validate the form and map it to model attributes (shared by store and update).
     */
    private function payload(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:Draft,Published'],
            'published_at' => ['required_if:status,Published', 'nullable', 'date'],
        ]);

        $data['is_published'] = $data['status'] === 'Published';
        $data['published_at'] = $data['is_published'] ? $data['published_at'] : null;
        unset($data['image'], $data['status']);

        return $data;
    }
}
