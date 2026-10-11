<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\GobikerMessage;
use Illuminate\Support\Facades\URL;

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