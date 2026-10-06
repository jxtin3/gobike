<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - Go Bike Project</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">

    <div class="login-split" id="loginSplit">

       <!-- LEFT PANEL -->
        <div class="login-split-left">

            <div class="login-brand-copy">
                <h1>Go Bike<br>Project</h1>
                <p>Community health responders serving&nbsp;Pangasinan since&nbsp;2019. Sign in to manage donations, news, and volunteer operations.</p>
            </div>

            <p class="login-brand-foot">&copy; {{ date('Y') }} Go Bike Project. All rights reserved.</p>
        </div>

        <!-- RIGHT PANEL  -->
        <div class="login-split-right">

            <!-- Top brand strip -->
            <div class="login-right-brand">
                <img
                    src="{{ asset('images/gobike-logo.png') }}"
                    alt="Go Bike"
                    class="login-right-brand-logo"
                >
                <div>
                    <span class="login-right-brand-name">Go Bike Project</span>
                    <span class="login-right-brand-sep">·</span>
                    <span class="login-right-brand-desc">Admin Portal</span>
                </div>
            </div>

            <div class="login-form-wrap">
                <div class="login-card">

                <div class="login-form-head">
                    <h2>Welcome back</h2>
                    <p>Sign in to manage donations, news, and volunteer operations.</p>
                </div>

                @if ($errors->any())
                    <div class="login-error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form
                    id="loginForm"
                    method="POST"
                    action="{{ route('login') }}"
                    class="login-form"
                    x-data="{ loading: false }"
                    @submit.prevent="
                        loading = true;
                        fetch($el.action, {
                            method: 'POST',
                            body: new FormData($el),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': $el.querySelector('[name=_token]').value
                            },
                            credentials: 'same-origin'
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Login failed');
                            return res.json();
                        })
                        .then(data => {
                            document.getElementById('loginSplit').classList.add('is-exiting');
                            setTimeout(() => { window.location.href = data.redirect || '/admin'; }, 720);
                        })
                        .catch(() => {
                            loading = false;
                            $el.submit();
                        });
                    "
                    @pageshow.window="loading = false"
                >
                    @csrf

                    {{-- Email --}}
                    <div class="login-field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                            placeholder="name@gmail.com"
                        >
                    </div>

                    {{-- Password --}}
                    <div class="login-field login-password" x-data="{ show: false }">
                        <label for="password">Password</label>
                        <input
                            x-ref="pwInput"
                            id="password"
                            name="password"
                            :type="show ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            class="login-password-toggle"
                            @click="show = !show"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                        >
                            {{-- Eye icon --}}
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            {{-- Eye-off icon --}}
                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Remember me --}}
                    <label class="login-remember">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        :disabled="loading"
                        class="login-submit"
                    >
                        <svg x-show="loading" x-cloak class="login-spinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
                    </button>

                </form>

                <p class="login-footnote">
                    Access is limited to PadyaRescue, Inc. administrators.
                </p>

                </div>{{-- /.login-card --}}
            </div>
        </div>

    </div>

    <!-- Alpine.js -->
    <script defer src="{{ asset('js/alpine-intersect.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine-collapse.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

</body>
</html>