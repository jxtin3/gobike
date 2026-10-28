<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Push\PushNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The admin writes announcements that every GoBiker sees in the mobile app. */
class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::with('author:id,name')
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.operations.announcements.index', [
            'announcements' => $announcements,
            'liveCount' => Announcement::live()->count(),
        ]);
    }

    public function create()
    {
        return view('admin.operations.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['created_by'] = $request->user()->id;

        $announcement = Announcement::create($data);
        if ($announcement->status_label === 'Live') {
            app(PushNotifier::class)->announcementPosted($announcement);
        }

        return redirect()->route('admin.operations.announcements.index')
            ->with('success', $data['is_active'] ? 'Announcement posted. GoBikers will see it in the app.' : 'Announcement saved as hidden.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.operations.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $wasLive = $announcement->status_label === 'Live';
        $announcement->update($this->payload($request, $announcement));

        // Only when it becomes visible to GoBikers, not on every edit.
        if (! $wasLive && $announcement->status_label === 'Live') {
            app(PushNotifier::class)->announcementPosted($announcement);
        }

        return redirect()->route('admin.operations.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    private function payload(Request $request, ?Announcement $existing = null): array
    {
        // An end date already on the announcement may stay even if it has passed meanwhile.
        $unchangedEnd = $existing?->expires_at?->format('Y-m-d\TH:i') === $request->input('expires_at');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', ...($unchangedEnd ? [] : ['after:now'])],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['expires_at'] = $data['expires_at'] ?? null;

        return $data;
    }
}
