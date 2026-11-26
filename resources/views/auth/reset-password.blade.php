@extends('auth.layout')

@section('title', 'Choose a new password')
@section('heading', 'Choose a new password')
@section('lead')
    We sent a 6-digit code to <strong>{{ $email }}</strong> if it has an account. It expires in {{ $minutes }} minutes.
@endsection

@section('card')
    <form method="POST" action="{{ route('password.update') }}" class="login-form"
          x-data="{ loading: false }" @submit="loading = true" @pageshow.window="loading = false">
        @csrf

        <div class="login-field">
            <label for="code">Code from the email</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6"
                   autocomplete="one-time-code" required autofocus placeholder="000000">
        </div>

        <div class="login-field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   placeholder="At least 12 characters, with letters and numbers">
        </div>

        <div class="login-field">
            <label for="password_confirmation">Type it again</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   autocomplete="new-password" required>
        </div>

        <button type="submit" class="login-submit" :disabled="loading">
            <span x-text="loading ? 'Saving…' : 'Change password'">Change password</span>
        </button>
    </form>

    <form method="POST" action="{{ route('password.email') }}" class="login-links">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        No email? Check spam, or
        <button type="submit" class="login-link-button">send a new code</button>
        (after a minute).
        · <a href="{{ route('password.request') }}">Use another email</a>
    </form>
@endsection
