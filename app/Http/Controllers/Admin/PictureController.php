<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Picture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PictureController extends Controller
{
    public function index(Request $request)
    {
        $pictures = Picture::query()
            ->latest()
            ->paginate(18);

        return view('admin.operations.pictures.index', compact('pictures'));
    }

    public function create()
    {
        return redirect()->route('admin.operations.pictures.index');
    }

    public function store(Request $request)
    {
        $files = [];
        if ($request->hasFile('file')) {
            $files = [$request->file('file')];
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        } elseif ($request->hasFile('files')) {
            $files = is_array($request->file('files')) ? $request->file('files') : [$request->file('files')];
        }

        if (empty($files)) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were received by the server. Please check file sizes.',
                ], 422);
            }
            return back()->with('error', 'Please select at least one photo or video to upload.');
        }

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,m4v', 'max:51200'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,m4v', 'max:51200'],
            'files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,m4v', 'max:51200'],
        ]);

        $created = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;

            $path = $file->store('pictures', 'public');
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $pic = Picture::create([
                'title' => $title ?: 'Media',
                'description' => null,
                'image_path' => $path,
                'uploaded_by' => $request->user()?->id,
            ]);

            $created[] = [
                'id' => $pic->id,
                'src' => asset('storage/' . $path),
                'isVideo' => $pic->isVideo(),
            ];
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => count($created) . ' file(s) successfully uploaded.',
                'items' => $created,
            ]);
        }

        return redirect()->route('admin.operations.pictures.index')
            ->with('success', count($created) . ' file(s) successfully uploaded.');
    }

    public function show(Picture $picture)
    {
        return view('admin.operations.pictures.show', compact('picture'));
    }

    public function edit(Picture $picture)
    {
        return redirect()->route('admin.operations.pictures.index');
    }

    public function update(Request $request, Picture $picture)
    {
        return redirect()->route('admin.operations.pictures.index');
    }

    public function destroy(Picture $picture)
    {
        if ($picture->image_path && Storage::disk('public')->exists($picture->image_path)) {
            Storage::disk('public')->delete($picture->image_path);
        }
        $picture->delete();

        return back()->with('success', 'Photo/video successfully removed.');
    }
}
