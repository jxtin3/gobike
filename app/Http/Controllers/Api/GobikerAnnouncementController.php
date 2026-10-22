<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

/** The announcements the admin posted for GoBikers: the live ones, newest first. */
class GobikerAnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::live()->latest()->limit(30)->get();

        return response()->json([
            'announcements' => $announcements->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'posted_at' => $announcement->created_at->toIso8601String(),
                'expires_at' => $announcement->expires_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
