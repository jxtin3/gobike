<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MFA Setup - Go Bike</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">

<div class="login-split" id="loginSplit">
    <!-- LEFT PANEL -->
    <div class="login-split-left" style="--login-bg: url('{{ asset('images/login-bg.jpg') }}')">

        <div class="login-brand-copy">
            <h1>Go Bike<br>Project</h1>
            <p>Enable Multi-Factor Authentication to secure your admin account.</p>
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
                <h2>Set up MFA</h2>
                <p>Scan the QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.).</p>
            </div>

            @if ($errors->any())
                <div class="login-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mfa-qr-section" style="text-align: center; margin-bottom: 1.5rem;">
                <div style="display: inline-block; padding: 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <img src="{{ $qrCodeUrl }}" alt="MFA QR Code" style="width: 200px; height: 200px; display: block;">
                </div>
                <p class="hint" style="margin-top: 0.75rem; color: #64748b; font-size: 0.875rem;">
                    Secret key: <code style="font-size: 0.8rem; background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $secret }}</code>
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('mfa.verify') }}"
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
                    <label for="code">6-digit code from authenticator app</label>
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
                    <span x-text="loading ? 'Verifying…' : 'Verify & Enable MFA'"></span>
                </button>

                <a href="{{ route('admin.profile.show') }}" class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 0.75rem;">Cancel</a>

            </form>

            <p class="login-footnote">
                Once enabled, you'll need a 6-digit code from your authenticator app each time you sign in.
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