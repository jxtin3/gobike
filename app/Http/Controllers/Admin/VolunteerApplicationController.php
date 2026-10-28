<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerApplication;
use App\Models\VolunteerClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Volunteer applications and their training classes. Volunteers are trained together:
 * once 20–25 are waiting, the admin schedules a class with a date and venue and calls them.
 */
class VolunteerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = VolunteerApplication::with('volunteerClass')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('barangay', 'like', "%{$search}%")
                    ->orWhere('municipality', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('application_status', $status);
        }

        if ($classId = $request->integer('class')) {
            $query->where('volunteer_class_id', $classId);
        }

        // The oldest waiting volunteers are first in line, so list them oldest first.
        if ($status === 'waiting') {
            $query->reorder()->oldest()->orderBy('id');
        }

        $counts = VolunteerApplication::query()
            ->selectRaw('application_status, COUNT(*) as total')
            ->groupBy('application_status')
            ->pluck('total', 'application_status');

        return view('admin.operations.volunteers.index', [
            'applications' => $query->paginate(20)->withQueryString(),
            'counts' => $counts,
            'totalCount' => $counts->sum(),
            'waitingCount' => (int) ($counts['waiting'] ?? 0),
            'classMin' => (int) config('volunteer.class_min'),
            'classMax' => (int) config('volunteer.class_max'),
            'classes' => VolunteerClass::withCount('applications')
                ->orderByRaw('CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('starts_on')
                ->limit(12)
                ->get(),
        ]);
    }

    public function show(VolunteerApplication $volunteerApplication)
    {
        return view('admin.operations.volunteers.show', [
            'application' => $volunteerApplication->load('volunteerClass'),
        ]);
    }

    public function updateStatus(VolunteerApplication $volunteerApplication, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'application_status' => ['required', Rule::in(VolunteerApplication::STATUSES)],
        ]);

        // Back to the queue (or rejected): no longer in a class.
        if (in_array($data['application_status'], ['pending', 'waiting', 'rejected'], true)) {
            $data['volunteer_class_id'] = null;
        }

        $volunteerApplication->update($data);

        return back()->with('success', "{$volunteerApplication->full_name}'s application marked {$volunteerApplication->status_label}.");
    }

    /** Puts the volunteers who have waited longest (up to a full class) into a new class. */
    public function scheduleClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_on' => ['required', 'date_format:Y-m-d', 'after:today'],
            'venue' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'starts_on.after' => 'Choose a class date from tomorrow onwards.',
        ]);

        $ids = VolunteerApplication::where('application_status', 'waiting')
            ->whereNull('volunteer_class_id')
            ->oldest()
            ->orderBy('id')
            ->limit((int) config('volunteer.class_max'))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'No volunteers are waiting for a class. Mark new applications as "Waiting for class" first.');
        }

        $class = DB::transaction(function () use ($data, $ids) {
            $class = VolunteerClass::create($data);
            VolunteerApplication::whereIn('id', $ids)->update([
                'volunteer_class_id' => $class->id,
                'application_status' => 'scheduled',
            ]);

            return $class;
        });

        return redirect()
            ->route('admin.operations.volunteers.index', ['class' => $class->id])
            ->with('success', "Class scheduled for {$class->label} with {$ids->count()} volunteers. Call them with the date and venue.");
    }

    /** The class took place: everyone in it is trained. */
    public function completeClass(VolunteerClass $volunteerClass): RedirectResponse
    {
        DB::transaction(function () use ($volunteerClass) {
            $volunteerClass->update(['completed_at' => now()]);
            $volunteerClass->applications()->where('application_status', 'scheduled')->update(['application_status' => 'trained']);
        });

        return back()->with('success', "Class on {$volunteerClass->starts_on->format('M j')} marked as done. Its volunteers are now trained.");
    }

    public function destroy(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $name = $volunteerApplication->full_name;
        $volunteerApplication->delete();

        return redirect()->route('admin.operations.volunteers.index')->with('success', "Application from {$name} deleted.");
    }
}
