<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Barangays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private array $roles = ['GoBiker', 'User'];

    public function index(Request $request)
    {
        $filter = in_array($request->query('filter'), ['pending', ...$this->roles], true)
            ? $request->query('filter')
            : 'all';

        $users = User::query()
            ->where('role', '!=', 'Admin')
            ->where('is_admin', false)
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($filter === 'pending', fn ($q) => $q->pendingApproval())
            ->when(in_array($filter, $this->roles, true), fn ($q) => $q->where('role', $filter))
            ->orderByRaw("CASE WHEN role = 'GoBiker' AND status = 'Inactive' THEN 0 ELSE 1 END") // pending first
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $byRole = User::query()
            ->where('role', '!=', 'Admin')
            ->where('is_admin', false)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $counts = [
            'all' => $byRole->sum(),
            'pending' => User::pendingApproval()->count(),
            'GoBiker' => $byRole['GoBiker'] ?? 0,
            'User' => $byRole['User'] ?? 0,
        ];

        return view('admin.operations.users.index', compact('users', 'filter', 'counts'));
    }

    public function create()
    {
        return view('admin.operations.users.create', ['roles' => $this->roles]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/', 'unique:users,mobile'],
            'barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            'role' => ['required', Rule::in($this->roles)],
        ]);

        $data['status'] = 'Active'; // accounts created by an admin no need for approval
        User::create($data);

        return redirect()->route('admin.operations.users.index')->with('success', 'User created.');
    }

    public function show(User $user)
    {
        if ($user->is_admin || $user->role === 'Admin') {
            return redirect()->route('admin.operations.users.index')
                ->with('error', 'Admin accounts are managed from the sidebar profile.');
        }

        return view('admin.operations.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if ($user->is_admin || $user->role === 'Admin') {
            return redirect()->route('admin.operations.users.index')
                ->with('error', 'Admin accounts are managed from the sidebar profile.');
        }

        return view('admin.operations.users.edit', ['user' => $user, 'roles' => $this->roles]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->is_admin || $user->role === 'Admin') {
            return redirect()->route('admin.operations.users.index')
                ->with('error', 'Admin accounts are managed from the sidebar profile.');
        }

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'mobile' => [
                'nullable',
                'string',
                'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'mobile')->ignore($user),
            ],
            'barangay' => ['nullable', 'string', Rule::in(Barangays::ALL)],
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->numbers()],
            'role' => ['required', Rule::in($this->roles)],
            ...$this->scheduleRules(),
        ]);
        $data = $this->withSchedule($data);

        $passwordChanged = filled($data['password'] ?? null);

        if (! $passwordChanged) {
            unset($data['password']);
        }

        // A pending GoBiker who is reclassified no longer needs approval.
        if ($user->isPendingApproval() && $data['role'] !== 'GoBiker') {
            $data['status'] = 'Active';
        }

        $user->update($data);

        // A new password signs the user out of the mobile app everywhere.
        if ($passwordChanged) {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.operations.users.show', $user)->with('success', 'User updated.');
    }

    /** The GoBiker's ronda schedule: some days plus a start and end time, or nothing at all. */
    private function scheduleRules(): array
    {
        return [
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => [Rule::in(User::SCHEDULE_DAYS)],
            'schedule_start' => ['nullable', 'required_with:schedule_days,schedule_end', 'date_format:H:i'],
            'schedule_end' => ['nullable', 'required_with:schedule_days,schedule_start', 'date_format:H:i', 'after:schedule_start'],
        ];
    }

    /** Clears the schedule when no day is ticked, and keeps it only for GoBikers. */
    private function withSchedule(array $data): array
    {
        $days = array_values($data['schedule_days'] ?? []);
        $keep = $days !== [] && ($data['role'] ?? null) === 'GoBiker';

        $data['schedule_days'] = $keep ? $days : null;
        $data['schedule_start'] = $keep ? $data['schedule_start'] : null;
        $data['schedule_end'] = $keep ? $data['schedule_end'] : null;

        return $data;
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user()) || $user->is_admin || $user->role === 'Admin') {
            return back()->with('error', 'Admin accounts cannot be deleted from user management.');
        }

        // Patient records are health data the GoBiker collected; never remove them as a side effect.
        $patientCount = $user->patients()->count();

        if ($patientCount > 0) {
            return back()->with('error', "{$user->name} has recorded ".trans_choice('{1} 1 patient record|[2,*] :count patient records', $patientCount).'. Change the role to User instead of deleting the account.');
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('admin.operations.users.index')->with('success', 'User deleted.');
    }

    // Let a pending GoBiker sign in to the mobile app.
    public function approve(User $user): RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return back()->with('error', "{$user->name} is not waiting for approval.");
        }

        $user->update(['status' => 'Active']);

        return back()->with('success', "{$user->name} was approved and can now sign in to the GoBiker app.");
    }

    // Remove a pending sign-up. They can register again later.
    public function decline(User $user): RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return back()->with('error', 'Only pending sign-ups can be declined.');
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('admin.operations.users.index', ['filter' => 'pending'])
            ->with('success', "The sign-up from {$user->name} was declined and removed.");
    }
}
