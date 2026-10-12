<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\GobikerMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        
        // Runs only when the admin sidebar renders, so public pages are unaffected.
        View::composer('admin.partials.sidebar', function ($view) {
            $view->with([
                'navPending'        => User::pendingApproval()->count(),
                'navUnread'         => ContactMessage::where('is_read', false)->count(),
                'navGobikerUnread'  => GobikerMessage::where('is_read', false)->count(),
            ]);
        });
    }
}