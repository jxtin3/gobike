<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\GobikerMessage;
use App\Models\News;
use App\Models\Patient;
use App\Models\Picture;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;


class OperationsController extends Controller
{
    public function index()
    {
        return view('admin.operations.index', [
            'newsCount'             => News::count(),
            'publishedNewsCount'    => News::where('is_published', true)->count(),
            'pictureCount'          => Picture::count(),
            'userCount'             => User::count(),
            'pendingCount'          => User::pendingApproval()->count(),
            'messageCount'          => ContactMessage::count(),
            'unreadCount'           => ContactMessage::where('is_read', false)->count(),
            'patientCount'          => Patient::count(),
            'gobikerMessageCount'   => GobikerMessage::count(),
            'gobikerUnreadCount'    => GobikerMessage::where('is_read', false)->count(),
            'volunteerCount'        => VolunteerApplication::count(),
            'volunteerPendingCount' => VolunteerApplication::where('application_status', 'pending')->count(),
            'donationCount'         => Donation::where('status', 'paid')->count(),
            'donationTotal'         => (float) Donation::where('status', 'paid')->sum('amount_usd'),
            'trainingCount'         => TrainingRequest::count(),
            'trainingPendingCount'  => TrainingRequest::where('status', 'pending')->count(),
        ]);
    }
}