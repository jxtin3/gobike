<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in($this->roles)],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_admin'] = false;
        $data['status'] = 'Active'; // accounts created by an admin no need for approval
        User::create($data);

        return redirect()->route('admin.operations.users.index')->with('success', 'User successfully created.');
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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in($this->roles)],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // A pending GoBiker who is reclassified no longer needs approval.
        if ($user->isPendingApproval() && $data['role'] !== 'GoBiker') {
            $data['status'] = 'Active';
        }

        $data['is_admin'] = false;
        $user->update($data);

        return redirect()->route('admin.operations.users.index')->with('success', 'User successfully updated.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user()) || $user->is_admin || $user->role === 'Admin') {
            return back()->with('error', 'Admin accounts cannot be deleted from user management.');
        }

        $user->delete();

        return back()->with('success', 'User successfully deleted.');
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

    //  Remove a pending sign-up. They can register again later. 
    public function decline(User $user): RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return back()->with('error', 'Only pending sign-ups can be declined.');
        }

        $user->delete();

        return redirect()->route('admin.operations.users.index', ['filter' => 'pending'])
            ->with('success', "The sign-up from {$user->name} was declined and removed.");
    }
}