<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Listeners\AuthenticationListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Spatie\Permission\PermissionRegistrar;

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
        // Register observers
        User::observe(UserObserver::class);

        // Register authentication event listeners
        Event::listen(Login::class, [AuthenticationListener::class, 'handleLogin']);
        Event::listen(Logout::class, [AuthenticationListener::class, 'handleLogout']);
        Event::listen(Failed::class, [AuthenticationListener::class, 'handleFailed']);
        Event::listen(PasswordReset::class, [AuthenticationListener::class, 'handlePasswordReset']);

        // Cache roles and permissions for better performance
        app()->make(PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache';

        // Force app URL dan HTTPS saat pakai Ngrok atau APP_URL custom
        if (env('APP_ENV') === 'local') {
            URL::forceRootUrl(config('app.url'));

            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Production environment optimizations
        if (app()->isProduction()) {
            // Force HTTPS in production
            URL::forceScheme('https');

            // Disable lazy loading to prevent N+1 queries
            Model::preventLazyLoading();

            // Prevent silently discarding attributes
            Model::preventSilentlyDiscardingAttributes();

            // Prevent accessing missing attributes
            Model::preventAccessingMissingAttributes();
        }

        // Development environment helpers
        if (app()->environment('local', 'development')) {
            // Log slow queries (queries taking more than 1000ms)
            DB::listen(function ($query) {
                if ($query->time > 1000) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]);
                }
            });
        }

        // Redis connection test and error handling
        try {
            Cache::driver('redis')->get('test');
        } catch (\Exception $e) {
            Log::warning('Redis connection failed, falling back to file cache', [
                'error' => $e->getMessage(),
            ]);
            config(['cache.default' => 'file']);
        }
    }
}
