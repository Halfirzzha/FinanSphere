<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class AuthenticationListener
{
    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if ($user && method_exists($user, 'updateLoginInfo')) {
            $user->updateLoginInfo();
        }
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user && method_exists($user, 'logActivity')) {
            $user->logActivity('logout', 'User logged out successfully');
        }
    }

    /**
     * Handle failed login attempt.
     */
    public function handleFailed(Failed $event): void
    {
        // Log failed login attempts
        Log::warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? null,
            'username' => $event->credentials['username'] ?? null,
            'ip_private' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle password reset event.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;

        if ($user && method_exists($user, 'update')) {
            $user->update([
                'password_changed_at' => now(),
                'password_changed_by' => 'self',
                'password_change_count' => ($user->password_change_count ?? 0) + 1,
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
