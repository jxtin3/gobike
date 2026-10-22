<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $changingSensitive = $request->input('email') !== strtolower($user->email)
            || filled($request->input('password'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->numbers()],
            'current_password' => [Rule::requiredIf($changingSensitive), 'nullable', 'current_password'],
        ]);

        unset($data['current_password']);

        $passwordChanged = filled($data['password'] ?? null);

        if (! $passwordChanged) {
            unset($data['password']);
        }

        $user->update($data);

        // Sign out every other browser that was using the old password.
        if ($passwordChanged) {
            Auth::logoutOtherDevices($request->input('password'));
            $request->session()->regenerate();
        }

        return redirect()->route('admin.profile.show')->with('success', 'Profile updated.');
    }
}
