<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'tier'    => ['required', Rule::in(TrainingRequest::OFFERED)],
            // Individual training starts on the person's own date, from tomorrow up to 60 days ahead.
            'preferred_date'   => ['required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:'.now()->addDays(60)->toDateString()],
            'preferred_window' => ['nullable', Rule::in(array_keys(Appointment::WINDOWS))],
            'id_documents'   => ['required', 'array', 'min:1', 'max:'.TrainingRequest::MAX_ID_FILES],
            'id_documents.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'preferred_date.after' => 'Choose a date from tomorrow onwards.',
            'preferred_date.before_or_equal' => 'Choose a date within the next 60 days.',
            'id_documents.required' => 'Please upload a photo of your valid ID.',
            'id_documents.max' => 'You can upload up to '.TrainingRequest::MAX_ID_FILES.' files.',
            'id_documents.*.mimes' => 'Each file must be a JPG, PNG, WEBP or PDF.',
            'id_documents.*.max' => 'A file is too big. Please upload files under 5 MB each.',
            'id_documents.*.uploaded' => 'A file could not be uploaded. Please try a smaller one.',
        ]);

        // IDs go on the private disk; only signed-in admins can open them.
        $data['id_paths'] = collect($request->file('id_documents'))
            ->map(fn ($file) => $file->store('training-ids', config('filesystems.private_disk')))
            ->all();
        unset($data['id_documents']);

        $training = TrainingRequest::create($data);

        // Land on the thank-you note instead of the top of the page
        return back()->withFragment('training-thanks')->with('training_success', [
            'first_name' => strtok(trim($data['name']), ' '),
            'plan' => TrainingRequest::TIERS[$data['tier']],
            'email' => $data['email'],
            'schedule' => $training->schedule_label,
        ]);
    }
}
