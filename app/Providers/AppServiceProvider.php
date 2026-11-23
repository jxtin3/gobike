<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\SosRequest;
use App\Support\AdminLiveFeed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\Push\FcmSender::class, \App\Services\Push\GoogleFcmSender::class);
        // The QR-capable subclass: its getQRCodeInline() needs bacon/bacon-qr-code autoloadable.
        $this->app->singleton(Google2FA::class, fn () => new \PragmaRX\Google2FAQRCode\Google2FA());
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('mobile', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('chatbot', function (Request $request) {
            return [
                Limit::perMinute(20)->by('chatbot-minute:'.$request->ip()),
                Limit::perHour(200)->by('chatbot-hour:'.$request->ip()),
            ];
        });

        // Admin sidebar state, remembered in two plain cookies, so the first byte is already right (no flash).
        // This sits on the layout because <body> is written before the sidebar is included.
        View::composer('admin.operations.layout', function ($view) {
            $groups = ['gobike', 'requests', 'content'];
            $closed = explode('.', (string) request()->cookie('gobike_admin_closed', ''));

            $view->with([
                'sideRail' => request()->cookie('gobike_admin_rail') === '1',
                'sideClosed' => array_values(array_intersect($groups, $closed)),
            ]);
        });

        // Runs only when the admin sidebar renders, so public pages are unaffected.
        // The same counts are polled by resources/js/admin/live-updates.js (see App\Support\AdminLiveFeed).
        View::composer('admin.partials.sidebar', function ($view) {
            $counts = AdminLiveFeed::counts();

            $view->with([
                'navEmergencies' => Location::unansweredEmergencies()->count(),
                'navSos' => SosRequest::unanswered()->count(),
                'navAppointmentsPending' => $counts['appointments'],
                'navPending' => $counts['users'],
                'navUnread' => $counts['messages'],
                'navGobikerUnread' => $counts['gobiker-messages'],
                'navVolunteersPending' => $counts['volunteers'],
                'navTrainingPending' => $counts['training'],
                'navChaptersPending' => $counts['chapters'],
            ]);
        });
    }
}
