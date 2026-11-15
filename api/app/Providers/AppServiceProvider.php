<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for(
            'self-registration-register',
            fn (Request $request) => Limit::perMinute(5)
                ->by('self-registration-register|'.$request->ip().'|'.$request->route('role')),
        );
        RateLimiter::for(
            'self-registration-resend',
            fn (Request $request) => Limit::perMinute(3)
                ->by('self-registration-resend|'.$request->ip().'|'.$request->route('role')),
        );
        RateLimiter::for(
            'self-registration-verify',
            fn (Request $request) => Limit::perMinute(10)
                ->by('self-registration-verify|'.$request->ip().'|'.$request->route('role')),
        );
    }
}
