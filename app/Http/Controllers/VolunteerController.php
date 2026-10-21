<?php

namespace App\Http\Controllers;

use App\Models\VolunteerApplication;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VolunteerController extends Controller
{
    public function store(Request $request)
    {
        $isMinor = (int) $request->input('age') < 18;

        $data = $request->validate([
            'firstName'       => ['required', 'string', 'max:80'],
            'lastName'        => ['required', 'string', 'max:80'],
            'email'           => ['required', 'email', 'max:150'],
            'phone'           => ['required', 'string', 'max:30'],
            'age'             => ['required', 'integer', 'between:13,25'],
            'status'          => ['required', Rule::in(['student', 'working', 'out-of-school', 'other'])],
            'school'          => ['nullable', 'string', 'max:150'],
            'barangay'        => ['required', 'string', 'max:100'],
            'municipality'    => ['required', 'string', 'max:100'],
            'province'        => ['required', 'string', 'max:100'],
            'availability'    => ['required', Rule::in(['weekdays', 'weekends', 'both', 'flexible'])],
            'hasBike'         => ['required', Rule::in(['yes', 'no'])],
            'motivation'      => ['nullable', 'string', 'max:500'],
            'emergencyName'   => ['required', 'string', 'max:100'],
            'emergencyPhone'  => ['required', 'string', 'max:30'],
            'guardianName'    => [Rule::requiredIf($isMinor), 'nullable', 'string', 'max:100'],
            'guardianPhone'   => [Rule::requiredIf($isMinor), 'nullable', 'string', 'max:30'],
            'guardianConsent' => [Rule::requiredIf($isMinor), 'boolean'],
            'agree'           => ['accepted'],
        ]);

        VolunteerApplication::create([
            'first_name'        => $data['firstName'],
            'last_name'         => $data['lastName'],
            'email'             => $data['email'],
            'phone'             => $data['phone'],
            'age'               => $data['age'],
            'occupation_status' => $data['status'],
            'school'            => $data['school'] ?? null,
            'barangay'          => $data['barangay'],
            'municipality'      => $data['municipality'],
            'province'          => $data['province'],
            'availability'      => $data['availability'],
            'has_bike'          => $data['hasBike'] === 'yes',
            'motivation'        => $data['motivation'] ?? null,
            'emergency_name'    => $data['emergencyName'],
            'emergency_phone'   => $data['emergencyPhone'],
            'guardian_name'     => $isMinor ? $data['guardianName'] : null,
            'guardian_phone'    => $isMinor ? $data['guardianPhone'] : null,
            'guardian_consent'  => $isMinor ? (bool) $data['guardianConsent'] : false,
        ]);

        return response()->json(['message' => 'Application received.']);
    }
}