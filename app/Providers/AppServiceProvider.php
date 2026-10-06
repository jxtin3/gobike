<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\GobikerMessage;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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