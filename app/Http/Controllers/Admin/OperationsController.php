<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\GobikerMessage;
use App\Models\News;
use App\Models\Picture;
use App\Models\User;
use App\Models\Patient;


class OperationsController extends Controller
{
    public function index()
    {
        return view('admin.operations.index', [
            'newsCount' => News::count(),
            'publishedNewsCount' => News::where('is_published', true)->count(),
            'pictureCount' => Picture::count(),
            'userCount' => User::count(),
            'pendingCount' => User::pendingApproval()->count(),
            'messageCount' => ContactMessage::count(),
            'unreadCount' => ContactMessage::where('is_read', false)->count(),
            'patientCount' => Patient::count(),
            'gobikerMessageCount' => GobikerMessage::count(),
            'gobikerUnreadCount'  => GobikerMessage::where('is_read', false)->count(),
        ]);
    }
}