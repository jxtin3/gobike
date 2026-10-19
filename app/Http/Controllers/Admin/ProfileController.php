<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('admin.profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request)
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    
    public function update(Request $request)
    {
        $user = $request->user();

        $changingSensitive = $request->input('email') !== $user->email
            || filled($request->input('password'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', 'min:12'],
            'current_password' => [Rule::requiredIf($changingSensitive), 'nullable', 'current_password'],
        ]);

        unset($data['current_password']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.profile.show')->with('success', 'Profile updated successfully.');
    }



}
