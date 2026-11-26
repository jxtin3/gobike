@extends('auth.layout')

@section('title', 'Two-step login')
@section('brand', 'Enter the code from your authenticator app to finish signing in.')
@section('heading', 'Enter your code')
@section('lead', 'Open your authenticator app and type the 6-digit code for Go Bike.')

@section('card')
    <form method="POST" action="{{ route('mfa.verify.submit') }}" class="login-form"
          x-data="{ loading: false, backup: false }" @submit="loading = true" @pageshow.window="loading = false">
        @csrf

        <div class="login-field">
            <label for="code" x-text="backup ? 'Backup code' : '6-digit code'">6-digit code</label>
            <input id="code" name="code" type="text" required autofocus autocomplete="one-time-code"
                   :inputmode="backup ? 'text' : 'numeric'" :maxlength="backup ? 9 : 6"
                   :placeholder="backup ? 'xxxx-xxxx' : '000000'" inputmode="numeric" maxlength="6" placeholder="000000">
        </div>

        <button type="submit" class="login-submit" :disabled="loading">
            <span x-text="loading ? 'Checking…' : 'Sign in'">Sign in</span>
        </button>

        <p class="login-links" style="margin: 0;">
            <button type="button" class="login-link-button"
                    @click="backup = !backup; $nextTick(() => document.getElementById('code').focus())"
                    x-text="backup ? 'Use the authenticator code' : 'Use a backup code'">Use a backup code</button>
        </p>
    </form>

    <p class="login-links">
        Lost your phone and your backup codes? Ask the person who runs the Go Bike server to reset
        your two-step login.
        · <a href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection
