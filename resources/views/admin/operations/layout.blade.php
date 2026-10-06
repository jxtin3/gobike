<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Operations') | OneGoBike Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}">
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
    @stack('head')
</head>
<body class="adm{{ request()->routeIs('admin.dashboard') ? ' adm-dashboard'.(!empty($dashboardEntrance) ? ' adm-dashboard-entrance' : '') : (request()->routeIs('admin.live-map.*') ? ' adm-live-map' : '') }}">
    <a class="skip-link" href="#main">Skip to content</a>

    <div class="adm-shell">
        @include('admin.partials.sidebar')
        <div class="adm-scrim" data-nav-scrim></div>

        <div class="adm-body">
            <div class="adm-mobilebar">
                <button type="button" class="icon-btn" data-nav-toggle aria-label="Open menu" aria-expanded="false" aria-controls="adm-side">
                    <x-admin.icon name="menu" />
                </button>
                OneGoBike Admin
            </div>

            <main id="main" class="adm-main">
                @unless (request()->routeIs('admin.live-map.*'))
                <header class="page-head">
                    <div>
                        @hasSection('back')
                            <a href="@yield('back')" class="back-link"><x-admin.icon name="arrow-left" /> @yield('back_label', 'Back')</a>
                        @endif
                        <h1>@yield('heading', 'Operations')</h1>
                        @hasSection('subheading')
                            <p class="page-sub">@yield('subheading')</p>
                        @endif
                    </div>
                    @hasSection('heading-actions')
                        <div class="heading-actions">@yield('heading-actions')</div>
                    @endif
                    @hasSection('actions')
                        <div class="head-actions">@yield('actions')</div>
                    @endif
                </header>
                @endunless

                @if (session('success'))
                    <div class="alert success" role="status" data-dismissible data-autodismiss="6000">
                        <x-admin.icon name="check-circle" />
                        <div>{{ session('success') }}</div>
                        <button type="button" class="alert-close icon-btn" aria-label="Dismiss"><x-admin.icon name="x" /></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert error" role="alert" data-dismissible>
                        <x-admin.icon name="alert-circle" />
                        <div>{{ session('error') }}</div>
                        <button type="button" class="alert-close icon-btn" aria-label="Dismiss"><x-admin.icon name="x" /></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert error" role="alert">
                        <x-admin.icon name="alert-circle" />
                        <div>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('admin.partials.confirm')
    @stack('scripts')
</body>
</html>