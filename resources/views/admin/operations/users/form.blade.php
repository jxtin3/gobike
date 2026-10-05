@php
    $role = old('role', $user?->role ?? ($user?->is_admin ? 'Admin' : 'User'));
@endphp
<form class="form-card" action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <fieldset class="form-section">
        <legend>Account</legend>
        <div class="grid-2">
            <div class="field">
                <label for="name">Full name</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" required autocomplete="off" value="{{ old('name', $user?->name) }}">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input class="input @error('email') is-invalid @enderror" id="email" type="email" name="email" required autocomplete="off" value="{{ old('email', $user?->email) }}">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Password</legend>
        <div class="grid-2">
            <div class="field">
                <label for="password">{{ $user ? 'New password' : 'Password' }}</label>
                <input class="input @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" {{ $user ? '' : 'required' }}>
                <span class="hint">{{ $user ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.' }}</span>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" {{ $user ? '' : 'required' }}>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Access</legend>
        <div class="field" style="max-width: 320px">
            <label for="role">Role</label>
            <select class="input @error('role') is-invalid @enderror" id="role" name="role">
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                @endforeach
            </select>
            <span class="hint">Admins sign in to this panel. GoBikers and Users sign in to the mobile app.</span>
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ $button }}</button>
        <a class="btn btn-secondary" href="{{ route('admin.operations.users.index') }}">Cancel</a>
    </div>
</form>