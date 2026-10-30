<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EmailVolunteerClass;
use App\Mail\VolunteerClassScheduled;
use App\Models\VolunteerApplication;
use App\Models\VolunteerClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

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
            'classes' => VolunteerClass::withCount(['applications', 'applications as emailed_count' => fn ($q) => $q->whereNotNull('class_emailed_at')])
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

        // Back to the queue (or rejected): no longer in a class, so a new class emails them again.
        if (in_array($data['application_status'], ['pending', 'waiting', 'rejected'], true)) {
            $data['volunteer_class_id'] = null;
            $data['class_emailed_at'] = null;
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

        // Email everyone their date and venue once this page has been sent back.
        EmailVolunteerClass::dispatchAfterResponse($class->id);

        return redirect()
            ->route('admin.operations.volunteers.index', ['class' => $class->id])
            ->with('success', "Class scheduled for {$class->label} with {$ids->count()} volunteers. They are being emailed the date and venue now.");
    }

    /** Emails the class details to its volunteers who did not get them yet. */
    public function emailClass(VolunteerClass $volunteerClass): RedirectResponse
    {
        $left = $volunteerClass->applications()->where('application_status', 'scheduled')->whereNull('class_emailed_at')->count();
        if ($left === 0) {
            return back()->with('success', 'Everyone in this class has already been emailed.');
        }

        EmailVolunteerClass::dispatchAfterResponse($volunteerClass->id);

        return back()->with('success', "Sending the class details to {$left} ".str('volunteer')->plural($left).'.');
    }

    /** Emails one volunteer their class details again (e.g. they could not find the email). */
    public function emailOne(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $class = $volunteerApplication->volunteerClass;
        if (! $class) {
            return back()->with('error', "{$volunteerApplication->full_name} is not in a class yet.");
        }

        try {
            Mail::to($volunteerApplication->email, $volunteerApplication->full_name)
                ->send(new VolunteerClassScheduled($volunteerApplication, $class));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', "The email to {$volunteerApplication->email} could not be sent. Check the address, or call them instead.");
        }

        $volunteerApplication->forceFill(['class_emailed_at' => now()])->save();

        return back()->with('success', "Class details emailed to {$volunteerApplication->email}.");
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

    /** Shows one uploaded ID in the browser. Private: it is never reachable by a public URL. */
    public function idDocument(VolunteerApplication $volunteerApplication, int $index)
    {
        $disk = Storage::disk(config('filesystems.private_disk'));
        $path = $volunteerApplication->id_paths[$index] ?? null;
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $name = $volunteerApplication->full_name;
        if ($volunteerApplication->id_paths) {
            Storage::disk(config('filesystems.private_disk'))->delete($volunteerApplication->id_paths);
        }
        $volunteerApplication->delete();

        return redirect()->route('admin.operations.volunteers.index')->with('success', "Application from {$name} deleted.");
    }
}
