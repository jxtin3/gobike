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
                <input class="input @error('barangay') is-invalid @enderror" id="barangay" name="barangay" autocomplete="address-level3" value="{{ old('barangay', $user?->barangay) }}" aria-describedby="barangay-error">
                <x-admin.field-error name="barangay" />
            </div>
        </div>
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
                    <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                @endforeach
            </select>
            <x-admin.field-error name="role" />
        </div>
    </fieldset>

    @if ($user)
        @php
            $days = old('schedule_days', $user->schedule_days ?? []);
            $time = fn ($v) => $v ? substr($v, 0, 5) : '';
        @endphp
        <fieldset class="form-section" aria-describedby="schedule-hint">
            <legend>Ronda schedule <span class="muted">(GoBikers only)</span></legend>
            <p class="hint" id="schedule-hint">Shown on the GoBiker's dashboard in the app. Leave every day unticked for no schedule.</p>
            <div class="field">
                <span class="field-title" id="schedule-days-label">Days</span>
                <div class="day-picks" role="group" aria-labelledby="schedule-days-label">
                    @foreach (\App\Models\User::SCHEDULE_DAYS as $day)
                        <label class="check-row">
                            <input type="checkbox" name="schedule_days[]" value="{{ $day }}" @checked(in_array($day, $days, true))>
                            <span>{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
                <x-admin.field-error name="schedule_days" />
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="schedule_start">Starts</label>
                    <input class="input @error('schedule_start') is-invalid @enderror" id="schedule_start" type="time" name="schedule_start" value="{{ old('schedule_start', $time($user->schedule_start)) }}" aria-describedby="schedule_start-error">
                    <x-admin.field-error name="schedule_start" />
                </div>
                <div class="field">
                    <label for="schedule_end">Ends</label>
                    <input class="input @error('schedule_end') is-invalid @enderror" id="schedule_end" type="time" name="schedule_end" value="{{ old('schedule_end', $time($user->schedule_end)) }}" aria-describedby="schedule_end-error">
                    <x-admin.field-error name="schedule_end" />
                </div>
            </div>
        </fieldset>
    @endif

    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><x-admin.icon name="check" /> {{ $button }}</button>
        <a class="btn btn-secondary" href="{{ $user ? route('admin.operations.users.show', $user) : route('admin.operations.users.index') }}">Cancel</a>
    </div>
</form>