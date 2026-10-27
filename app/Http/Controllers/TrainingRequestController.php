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

        // Land on the thank-you note instead of the top of the page
        return back()->withFragment('training-thanks')->with('training_success', [
            'first_name' => strtok(trim($data['name']), ' '),
            'plan' => TrainingRequest::TIERS[$data['tier']],
            'email' => $data['email'],
        ]);
    }
}
