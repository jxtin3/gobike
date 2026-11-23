<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MFA Backup Codes - Go Bike</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">

<div class="login-split" id="loginSplit">
    <!-- LEFT PANEL -->
    <div class="login-split-left" style="--login-bg: url('{{ asset('images/login-bg.jpg') }}')">

        <div class="login-brand-copy">
            <h1>Go Bike<br>Project</h1>
            <p>MFA enabled. Save your backup codes in a safe place.</p>
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
                <h2>MFA Enabled</h2>
                <p class="hint" style="color: #16a34a;">Your authenticator app will now generate 6-digit codes for sign-in.</p>
            </div>

            <div class="callout" style="background: #fef3c7; border-color: #fcd34d; margin-bottom: 1.5rem;">
                <x-admin.icon name="exclamation-triangle" style="color: #b45309;" />
                <div>
                    <b>Important: Save your backup codes</b>
                    <span>Each code can be used once if you lose access to your authenticator app. Store them securely (password manager, printed, etc.). They will not be shown again.</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1.5rem; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.85rem;">
                @foreach ($backupCodes as $code)
                    <div style="padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; letter-spacing: 0.05em;">{{ $code }}</div>
                @endforeach
            </div>

            <div class="form-actions">
                <a class="btn btn-ok" href="{{ route('admin.dashboard') }}"><x-admin.icon name="check" /> Continue to Dashboard</a>
            </div>

            <p class="login-footnote">
                You can view backup codes again from your profile after signing in with MFA.
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