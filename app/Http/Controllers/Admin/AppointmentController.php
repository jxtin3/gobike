<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Push\PushNotifier;
use Illuminate\Http\Request;

/**
 * Residents' home-visit requests. The resident chooses a GoBiker in the app, who accepts or
 * declines there; the admin follows them and can still decline one with a note.
 */
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
        $appointment->load('resident', 'gobiker:id,name,mobile,barangay', 'patient');

        return view('admin.operations.appointments.show', ['appointment' => $appointment]);
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
        $appointment = $appointment->fresh(['resident', 'gobiker']);
        app(PushNotifier::class)->appointmentChanged($appointment);
        if ($appointment->gobiker) {
            app(PushNotifier::class)->appointmentWithdrawn($appointment, 'admin');
        }

        return back()->with('success', 'Request declined. The resident will see your note in the app.');
    }
}
