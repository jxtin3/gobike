@extends('auth.layout')

@section('title', 'Forgot password')
@section('heading', 'Forgot password')
@section('lead', 'Enter the email of your account. We will email you a 6-digit code to choose a new password.')

@section('card')
    <form method="POST" action="{{ route('password.email') }}" class="login-form"
          x-data="{ loading: false }" @submit="loading = true" @pageshow.window="loading = false">
        @csrf

        <div class="login-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   autocomplete="email" required autofocus placeholder="name@gmail.com">
        </div>

        <button type="submit" class="login-submit" :disabled="loading">
            <span x-text="loading ? 'Sending…' : 'Send code'">Send code</span>
        </button>
    </form>

    <p class="login-links"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
