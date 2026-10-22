<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The admin decides whether GoBikers should keep checking a person, or can stop. */
class PatientFollowUpController extends Controller
{
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(array_keys(PatientFollowUp::DECISIONS))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        PatientFollowUp::updateOrCreate(
            ['person_key' => $patient->personKey()],
            [
                'name' => trim($patient->name),
                'contact' => $patient->contact,
                'decision' => $data['decision'],
                'note' => $data['note'] ?? null,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ],
        );

        return back()->with('success', $data['decision'] === 'stop'
            ? "GoBikers will be told they can stop regular check-ups for {$patient->name}."
            : "GoBikers will be told to keep checking {$patient->name}.");
    }
}
