@extends('admin.operations.layout')
@section('title', 'Edit profile')
@section('heading', 'Edit profile')
@section('subheading', 'Update your name, email, or password.')
@section('back', route('admin.profile.show'))
@section('back_label', 'Back to profile')

@section('content')
<form class="form-card" action="{{ route('admin.profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <fieldset class="form-section">
        <legend>Account</legend>
        <div class="grid-2">
            <div class="field">
                <label for="name">Full name</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" required autocomplete="name" value="{{ old('name', $user->name) }}" aria-describedby="name-error">
                <x-admin.field-error name="name" />
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input class="input @error('email') is-invalid @enderror" id="email" type="email" name="email" required autocomplete="email" value="{{ old('email', $user->email) }}" aria-describedby="email-error">
                <x-admin.field-error name="email" />
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Password</legend>
        <div class="grid-2">
            <div class="field">
                <label for="password">New password</label>
                <input class="input @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" aria-describedby="password-hint password-error">
                <span class="hint" id="password-hint">At least 12 characters with letters and numbers. Leave blank to keep your current password. Other devices are signed out when you change it.</span>
                <x-admin.field-error name="password" />
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input @error('password_confirmation') is-invalid @enderror" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" aria-describedby="password_confirmation-error">
                <x-admin.field-error name="password_confirmation" />
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Multi-Factor Authentication</legend>
        @if ($user->hasMfaEnabled())
            <div class="field">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <span class="pill pill-ok" style="font-size: 0.875rem;">MFA Enabled</span>
                    <form method="POST" action="{{ route('admin.mfa.disable') }}" style="margin: 0;" data-confirm-title="Disable MFA?" data-confirm-text="You will no longer need a 6-digit code to sign in. This makes your account less secure." data-confirm-ok="Disable MFA" data-confirm-tone="warn">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><x-admin.icon name="shield-off" /> Disable MFA</button>
                    </form>
                    <a href="#" class="btn btn-secondary btn-sm" onclick="event.preventDefault(); alert('Backup codes are shown once at setup. If you lost them, disable and re-enable MFA.')"><x-admin.icon name="key" /> View backup codes</a>
                </div>
                <span class="hint">You'll enter a 6-digit code from your authenticator app each time you sign in.</span>
            </div>
        @else
            <div class="field">
                <form method="POST" action="{{ route('admin.mfa.setup') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-primary"><x-admin.icon name="shield-check" /> Enable MFA</button>
                </form>
                <span class="hint">Add an extra layer of security. You'll scan a QR code with an authenticator app (Google Authenticator, Authy, 1Password, etc.).</span>
            </div>
        @endif
    </fieldset>

    <fieldset class="form-section">
        <legend>Confirm it's you</legend>
        <div class="field">
            <label for="current_password">Current password</label>
            <input class="input @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" autocomplete="current-password" aria-describedby="current_password-hint current_password-error">
            <span class="hint" id="current_password-hint">Required only when changing your email or password.</span>
            <x-admin.field-error name="current_password" />
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><x-admin.icon name="check" /> Save changes</button>
        <a class="btn btn-secondary" href="{{ route('admin.profile.show') }}">Cancel</a>
    </div>
</form>
@endsection
