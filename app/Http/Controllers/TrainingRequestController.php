<?php

namespace App\Http\Controllers;

use App\Models\TrainingRequest;
use Illuminate\Http\Request;

class TrainingRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'tier'    => ['required', 'in:basic,standard,advance'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        TrainingRequest::create($data);

        return back()->with('training_success', 'Your training request has been submitted! Our team will contact you within 48 hours.');
    }
}
