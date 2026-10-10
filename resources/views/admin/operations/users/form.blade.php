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
            <div class="field">
                <label for="mobile">Mobile number</label>
                <input class="input @error('mobile') is-invalid @enderror" id="mobile" type="tel" name="mobile" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" autocomplete="tel" value="{{ old('mobile', $user?->mobile) }}">
                <span class="hint">Use a Philippine mobile number, e.g. 09123456789.</span>
            </div>
            <div class="field">
                <label for="barangay">Barangay</label>
                <input class="input @error('barangay') is-invalid @enderror" id="barangay" name="barangay" autocomplete="address-level3" value="{{ old('barangay', $user?->barangay) }}">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Password</legend>
        <div class="grid-2">
            <div class="field">
                <label for="password">{{ $user ? 'Set a new password' : 'Password' }}</label>
                <input class="input @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" {{ $user ? '' : 'required' }}>
                <span class="hint">{{ $user ? 'Passwords cannot be viewed. Leave blank to keep the current password.' : 'Minimum 8 characters.' }}</span>
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
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ $button }}</button>
        <a class="btn btn-secondary" href="{{ route('admin.operations.users.index') }}">Cancel</a>
    </div>
</form>