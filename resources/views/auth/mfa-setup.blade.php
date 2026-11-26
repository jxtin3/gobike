@extends('auth.layout')

@section('title', 'Set up two-step login')
@section('brand', 'Admins sign in with a password and a code from their phone.')
@section('heading', 'Set up two-step login')
@section('lead')
    @if ($required)
        Admins must use two-step login. Set it up once to open the admin area.
    @else
        Add a code from your phone to every sign-in.
    @endif
@endsection

@section('card')
    <ol class="login-links" style="margin: 0 0 1.25rem; padding-left: 1.2rem;">
        <li>Install an authenticator app, such as Google Authenticator or Microsoft Authenticator.</li>
        <li>In the app, add an account and scan this QR code.</li>
        <li>Type the 6-digit code the app shows.</li>
    </ol>

    <div class="mfa-qr">
        <img src="{{ $qrCodeUrl }}" alt="QR code for your authenticator app">
        <div class="mfa-key">
            Can't scan it? Type this key in the app:<br>
            <code>{{ $secret }}</code>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.mfa.verify') }}" class="login-form"
          x-data="{ loading: false }" @submit="loading = true" @pageshow.window="loading = false">
        @csrf

        <div class="login-field">
            <label for="code">6-digit code from the app</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6"
                   autocomplete="one-time-code" required autofocus placeholder="000000">
        </div>

        <button type="submit" class="login-submit" :disabled="loading">
            <span x-text="loading ? 'Checking…' : 'Turn on two-step login'">Turn on two-step login</span>
        </button>
    </form>

    <div class="login-links">
        @if ($required)
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="login-link-button">Sign out</button>
            </form>
        @else
            <a href="{{ route('admin.profile.show') }}">Cancel</a>
        @endif
    </div>
@endsection
