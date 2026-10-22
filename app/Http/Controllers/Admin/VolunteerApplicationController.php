<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VolunteerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = VolunteerApplication::latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('barangay', 'like', "%{$search}%")
                    ->orWhere('municipality', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('application_status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();

        return view('admin.operations.volunteers.index', [
            'applications' => $applications,
            'totalCount' => VolunteerApplication::count(),
            'pendingCount' => VolunteerApplication::where('application_status', 'pending')->count(),
            'approvedCount' => VolunteerApplication::where('application_status', 'approved')->count(),
        ]);
    }

    public function show(VolunteerApplication $volunteerApplication)
    {
        return view('admin.operations.volunteers.show', [
            'application' => $volunteerApplication,
        ]);
    }

    public function updateStatus(VolunteerApplication $volunteerApplication, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'application_status' => ['required', Rule::in(VolunteerApplication::STATUSES)],
        ]);

        $volunteerApplication->update($data);

        return back()->with('success', "{$volunteerApplication->full_name}'s application marked {$volunteerApplication->status_label}.");
    }

    public function destroy(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $name = $volunteerApplication->full_name;
        $volunteerApplication->delete();

        return redirect()->route('admin.operations.volunteers.index')->with('success', "Application from {$name} deleted.");
    }
}
