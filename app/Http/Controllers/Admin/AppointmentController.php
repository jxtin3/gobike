<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Residents' home-visit requests: the admin can assign a GoBiker or decline. */
class AppointmentController extends Controller
{
    private const TABS = ['pending', 'accepted', 'completed', 'closed', 'all'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('status'), self::TABS, true) ? $request->query('status') : 'pending';
        $search = trim((string) $request->query('search', ''));

        $appointments = Appointment::with('resident:id,name,mobile', 'gobiker:id,name')
            ->when($tab === 'closed', fn ($q) => $q->whereIn('status', ['declined', 'cancelled']))
            ->when(in_array($tab, ['pending', 'accepted', 'completed'], true), fn ($q) => $q->where('status', $tab))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('resident', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                        ->orWhere('barangay', 'like', "%{$search}%");
                });
            })
            ->orderBy('preferred_date')
            ->oldest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.appointments.index', [
            'appointments' => $appointments,
            'tab' => $tab,
            'counts' => [
                'pending' => Appointment::where('status', 'pending')->count(),
                'accepted' => Appointment::where('status', 'accepted')->count(),
                'completed' => Appointment::where('status', 'completed')->count(),
                'closed' => Appointment::whereIn('status', ['declined', 'cancelled'])->count(),
                'all' => Appointment::count(),
            ],
        ]);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load('resident', 'gobiker:id,name,mobile', 'patient');

        return view('admin.operations.appointments.show', [
            'appointment' => $appointment,
            // GoBikers who can take it: active ones, those in the same barangay first.
            'gobikers' => User::where('role', 'GoBiker')
                ->where('status', 'Active')
                ->orderByRaw('CASE WHEN barangay = ? THEN 0 ELSE 1 END', [$appointment->barangay])
                ->orderBy('name')
                ->get(['id', 'name', 'barangay']),
        ]);
    }

    public function assign(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'gobiker_id' => ['required', Rule::exists(User::class, 'id')->where('role', 'GoBiker')->where('status', 'Active')],
        ]);

        if (! $appointment->isOpen()) {
            return back()->with('error', 'This request is already '.strtolower($appointment->status_label).'.');
        }

        $appointment->update([
            'status' => 'accepted',
            'gobiker_id' => $data['gobiker_id'],
            'accepted_at' => $appointment->accepted_at ?? now(),
        ]);
        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);
        app(PushNotifier::class)->appointmentAssigned($appointment);

        return back()->with('success', 'GoBiker assigned. They will see the visit in the app.');
    }

    public function decline(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'decline_note' => ['required', 'string', 'max:500'],
        ]);

        if (! $appointment->isOpen()) {
            return back()->with('error', 'This request is already '.strtolower($appointment->status_label).'.');
        }

        $appointment->update(['status' => 'declined', 'decline_note' => $data['decline_note']]);
        app(PushNotifier::class)->appointmentChanged($appointment->fresh(['resident', 'gobiker']));

        return back()->with('success', 'Request declined. The resident will see your note in the app.');
    }
}
