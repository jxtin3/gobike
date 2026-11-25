@php
    $role = old('role', $user?->role ?? ($user?->is_admin ? 'Admin' : 'User'));
@endphp
<form class="form-card" action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <fieldset class="form-section">
        <legend>Account</legend>
        <div class="grid-2">
            <div class="field">
                <label for="name">Full name</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" required autocomplete="off" value="{{ old('name', $user?->name) }}" aria-describedby="name-error">
                <x-admin.field-error name="name" />
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input class="input @error('email') is-invalid @enderror" id="email" type="email" name="email" required autocomplete="off" value="{{ old('email', $user?->email) }}" aria-describedby="email-error">
                <x-admin.field-error name="email" />
            </div>
            <div class="field">
                <label for="mobile">Mobile number <span class="muted">(optional)</span></label>
                <input class="input @error('mobile') is-invalid @enderror" id="mobile" type="tel" name="mobile" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" autocomplete="tel" value="{{ old('mobile', $user?->mobile) }}" aria-describedby="mobile-hint mobile-error">
                <span class="hint" id="mobile-hint">Use a Philippine mobile number, e.g. 09123456789.</span>
                <x-admin.field-error name="mobile" />
            </div>
            <div class="field">
                <label for="barangay">Barangay <span class="muted">(optional)</span></label>
                @php
                    $barangay = old('barangay', $user?->barangay);
                @endphp
                <select class="input @error('barangay') is-invalid @enderror" id="barangay" name="barangay" aria-describedby="barangay-error">
                    <option value="" @selected(blank($barangay))>Not set</option>
                    @foreach (\App\Models\Barangay::orderBy('name')->pluck('name') as $name)
                        <option value="{{ $name }}" @selected($barangay === $name)>{{ $name }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="barangay" />
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Photo</legend>
        <div class="field">
            <label for="photo">{{ $user?->photo_path ? 'Replace photo' : 'Profile photo' }} <span class="muted">(optional)</span></label>
            <input class="input @error('photo') is-invalid @enderror" id="photo" type="file" name="photo" accept="image/jpeg,image/png"
                   aria-describedby="photo-hint photo-error">
            <span class="hint" id="photo-hint">JPG or PNG, up to 5 MB.{{ $user?->photo_path ? ' Leave empty to keep the current photo.' : ' The photo also shows in the mobile app.' }}</span>
            <x-admin.field-error name="photo" />
            @if ($user?->photo_path)
                <img class="form-preview" src="{{ \App\Support\Media::url($user->photo_path) }}" alt="Current photo of {{ $user->name }}">
            @endif
        </div>
        @if ($user?->photo_path)
            <label class="check-row">
                <input type="checkbox" name="remove_photo" value="1">
                Remove the current photo
            </label>
        @endif
    </fieldset>

    <fieldset class="form-section">
        <legend>Password</legend>
        <div class="grid-2">
            <div class="field">
                <label for="password">{{ $user ? 'Set a new password' : 'Password' }}</label>
                <input class="input @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" {{ $user ? '' : 'required' }} aria-describedby="password-hint password-error">
                <span class="hint" id="password-hint">At least 12 characters with letters and numbers.{{ $user ? ' Leave blank to keep the current password; a new one signs them out of the app.' : '' }}</span>
                <x-admin.field-error name="password" />
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input @error('password_confirmation') is-invalid @enderror" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" {{ $user ? '' : 'required' }} aria-describedby="password_confirmation-error">
                <x-admin.field-error name="password_confirmation" />
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Access</legend>
        <div class="field field-narrow">
            <label for="role">Role</label>
            <select class="input @error('role') is-invalid @enderror" id="role" name="role" aria-describedby="role-error">
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected($role === $r)>{{ \App\Models\User::roleLabelFor($r) }}</option>
                @endforeach
            </select>
            <x-admin.field-error name="role" />
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><x-admin.icon name="check" /> {{ $button }}</button>
        <a class="btn btn-secondary" href="{{ $user ? route('admin.operations.users.show', $user) : route('admin.operations.users.index') }}">Cancel</a>
    </div>
</form>