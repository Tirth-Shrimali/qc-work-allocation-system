<?php

namespace App\Providers;

use App\Models\AppNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with('unreadCount', $user
                ? AppNotification::where('user_id', $user->id)->where('is_read', false)->count()
                : 0);
        });

        Blade::if('permission', function (string $code) {
            $user = auth()->user();

            return $user && $user->hasPermission($code);
        });

        Blade::if('role', function (string ...$codes) {
            $user = auth()->user();

            return $user && $user->hasAnyRole($codes);
        });
    }
}
