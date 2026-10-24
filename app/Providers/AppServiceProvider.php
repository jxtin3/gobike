<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\GobikerMessage;
use App\Models\Location;
use App\Models\ReplicationRequest;
use App\Models\SosRequest;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
            $groups = ['people', 'requests', 'content'];
            $closed = explode('.', (string) request()->cookie('gobike_admin_closed', ''));

            $view->with([
                'sideRail' => request()->cookie('gobike_admin_rail') === '1',
                'sideClosed' => array_values(array_intersect($groups, $closed)),
            ]);
        });

        // Runs only when the admin sidebar renders, so public pages are unaffected.
        View::composer('admin.partials.sidebar', function ($view) {
            $view->with([
                'navEmergencies' => Location::unansweredEmergencies()->count(),
                'navSos' => SosRequest::unanswered()->count(),
                'navPending' => User::pendingApproval()->count(),
                'navUnread' => ContactMessage::where('is_read', false)->count(),
                'navGobikerUnread' => GobikerMessage::where('is_read', false)->count(),
                'navVolunteersPending' => VolunteerApplication::where('application_status', 'pending')->count(),
                'navTrainingPending' => TrainingRequest::where('status', 'pending')->count(),
                'navChaptersPending' => ReplicationRequest::where('status', 'pending')->count(),
            ]);
        });
    }
}
