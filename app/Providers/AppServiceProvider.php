<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Only force HTTPS in true production environments, never on localhost/127.0.0.1.
        // Forcing HTTPS on localhost causes browser requests to hang waiting for a TLS
        // handshake that never happens, resulting in 30+ minute load times.
        $isLocalhost = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
        $isForwardedHttps = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';

        if (!$isLocalhost && (config('app.env') === 'production' || isset($_SERVER['HTTPS']) || $isForwardedHttps)) {
            $_SERVER['HTTPS'] = 'on';
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
