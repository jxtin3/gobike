{{-- The split sign-in page (photo on the left, card on the right), shared by forgot password,
     reset password and the two-step login pages. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Go Bike</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">

<div class="login-split" id="loginSplit">
    <div class="login-split-left" style="--login-bg: url('{{ asset('images/login-bg.jpg') }}')">
        <div class="login-brand-copy">
            <h1>Go Bike<br>Project</h1>
            <p>@yield('brand', 'Community health responders serving Pangasinan since 2019.')</p>
        </div>
        <p class="login-brand-foot">&copy; {{ date('Y') }} Go Bike Project. All rights reserved.</p>
    </div>

    <div class="login-split-right">
        <div class="login-right-brand">
            <img src="{{ asset('images/gobike-logo.png') }}" alt="Go Bike" class="login-right-brand-logo">
            <div>
                <span class="login-right-brand-name">Go Bike Project</span>
                <span class="login-right-brand-sep">·</span>
                <span class="login-right-brand-desc">Admin Portal</span>
            </div>
        </div>

        <div class="login-form-wrap">
            <div class="login-card">
                <div class="login-form-head">
                    <h2>@yield('heading')</h2>
                    @hasSection('lead')
                        <p>@yield('lead')</p>
                    @endif
                </div>

                @if (session('status'))
                    <div class="login-status" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                @yield('card')
            </div>
        </div>
    </div>
</div>

<script defer src="{{ asset('js/alpine.min.js') }}"></script>
</body>
</html>
