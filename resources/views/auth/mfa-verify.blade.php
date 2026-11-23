<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MFA Verification - Go Bike</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">

<div class="login-split" id="loginSplit">
    <!-- LEFT PANEL -->
    <div class="login-split-left" style="--login-bg: url('{{ asset('images/login-bg.jpg') }}')">

        <div class="login-brand-copy">
            <h1>Go Bike<br>Project</h1>
            <p>Enter the 6-digit code from your authenticator app to complete sign-in.</p>
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
                <h2>Verify your identity</h2>
                <p>MFA is enabled for this account. Enter the code from your authenticator app.</p>
            </div>

            @if ($errors->any())
                <div class="login-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('mfa.verify.submit') }}"
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
                        if (!res.ok) throw new Error('Verification failed');
                        return res.json();
                    })
                    .then(data => {
                        if (data.redirect) {
                            document.getElementById('loginSplit').classList.add('is-exiting');
                            setTimeout(() => { window.location.href = data.redirect; }, 720);
                        }
                    })
                    .catch(() => {
                        loading = false;
                        $el.submit();
                    });
                "
                @pageshow.window="loading = false"
            >
                @csrf

                {{-- MFA Code --}}
                <div class="login-field">
                    <label for="code">6-digit code</label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        placeholder="000000"
                    >
                    @error('code')
                        <p class="login-field-error" style="color: #b42318; font-size: 0.8rem; margin-top: 0.3rem;">{{ $message }}</p>
                    @enderror
                </div>

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
                    <span x-text="loading ? 'Verifying…' : 'Sign in'"></span>
                </button>

            </form>

            <p class="login-footnote">
                <a href="#" onclick="event.preventDefault(); alert('Use a backup code from when you set up MFA, or contact another admin.')">
                    Lost access to your authenticator app?
                </a>
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