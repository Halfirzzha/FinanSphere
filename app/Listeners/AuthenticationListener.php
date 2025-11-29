<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AuthenticationListener
{
    /**
     * Handle user login attempt event.
     * ENHANCED: Pre-check account status and blocking before authentication
     */
    public function handleAttempting(Attempting $event): void
    {
        $credentials = $event->credentials;
        $identifier = $credentials['email'] ?? $credentials['username'] ?? 'unknown';

        // Find user to perform pre-checks
        $user = User::where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->first();

        if ($user) {
            // AUTO-UNLOCK: Check if block period has expired
            if ($user->account_status === User::STATUS_BLOCKED &&
                $user->blocked_until &&
                now()->greaterThan($user->blocked_until)) {

                $user->update([
                    'account_status' => User::STATUS_ACTIVE,
                    'failed_login_attempts' => 0,
                    'blocked_until' => null,
                    'locked_reason' => null,
                    'locked_at' => null,
                    'locked_by' => null,
                ]);

                $user->logActivity(
                    'account_auto_unlocked',
                    'Account automatically unlocked - block period expired',
                    ['unlocked_at' => now()->toDateTimeString()]
                );

                // Clear cache
                Cache::forget("login_blocked:{$identifier}");

                Log::info('Account auto-unlocked', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => request()->ip(),
                ]);

                return; // Allow login to proceed
            }

            // ENHANCED VALIDATION: Check account status
            if ($user->account_status !== User::STATUS_ACTIVE) {
                Log::warning('Login attempt on non-active account', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'account_status' => $user->account_status,
                    'is_active' => $user->is_active,
                    'is_locked' => $user->is_locked,
                    'blocked_until' => $user->blocked_until,
                    'ip' => request()->ip(),
                ]);
                return;
            }

            // ENHANCED VALIDATION: Check if still blocked
            if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
                $remainingMinutes = now()->diffInMinutes($user->blocked_until);

                Log::warning('Login attempt on temporarily blocked account', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'blocked_until' => $user->blocked_until,
                    'remaining_minutes' => $remainingMinutes,
                    'ip' => request()->ip(),
                ]);
                return;
            }
        }

        // Check cache for blocked IPs/identifiers
        $cacheKey = "login_blocked:{$identifier}";
        if (Cache::has($cacheKey)) {
            $blockedUntil = Cache::get($cacheKey);
            Log::warning('Blocked user attempted to login', [
                'identifier' => $identifier,
                'blocked_until' => $blockedUntil,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Handle successful user login event.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if ($user && $user instanceof User) {
            // Update login information with automatic tracking
            $user->updateLoginInfo();

            // Clear any login block cache
            $cacheKey = "login_blocked:{$user->email}";
            Cache::forget($cacheKey);

            // Log successful login
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user && $user instanceof User) {
            $user->logActivity('logout', 'User logged out successfully');

            Log::info('User logged out', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Handle failed login attempt with auto-blocking system.
     * ENHANCED: Better logging with detailed context and professional messages
     */
    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;

        if (!$identifier) {
            return;
        }

        // Try to find the user
        $user = User::where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->first();

        if ($user) {
            // ENHANCED: Check if account is already blocked (with detailed logging)
            if ($user->isBlocked()) {
                $blockDetails = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'account_status' => $user->account_status,
                    'blocked_until' => $user->blocked_until,
                    'blocked_by' => $user->blocked_by,
                    'locked_reason' => $user->locked_reason,
                    'failed_attempts' => $user->failed_login_attempts,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ];

                // Get admin info if blocked by admin
                if ($user->blocked_by) {
                    $admin = User::find($user->blocked_by);
                    if ($admin) {
                        $blockDetails['blocked_by_admin'] = $admin->full_name;
                        $blockDetails['admin_position'] = $admin->position;
                    }
                }

                Log::warning('Login attempt on blocked account - REJECTED', $blockDetails);
                return;
            }

            // ENHANCED: Check for non-active status
            if ($user->account_status !== User::STATUS_ACTIVE) {
                Log::warning('Login attempt on non-active account status - REJECTED', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'account_status' => $user->account_status,
                    'is_active' => $user->is_active,
                    'is_locked' => $user->is_locked,
                    'ip' => request()->ip(),
                ]);
                return;
            }

            // Handle failed login (will auto-block if threshold reached)
            // Note: Use skipIncrement=false to count this attempt
            $user->handleFailedLogin(false);

            // Refresh user to get updated values
            $user->refresh();

            // ENHANCED: If now blocked, add to cache with detailed info
            if ($user->account_status === User::STATUS_BLOCKED && $user->blocked_until) {
                $cacheKey = "login_blocked:{$identifier}";
                $cacheExpiry = now()->diffInSeconds($user->blocked_until);
                Cache::put($cacheKey, $user->blocked_until->toDateTimeString(), $cacheExpiry);

                // Also cache by username if using email
                if (isset($credentials['email'])) {
                    Cache::put("login_blocked:{$user->username}", $user->blocked_until->toDateTimeString(), $cacheExpiry);
                }

                Log::critical('Account auto-blocked due to failed attempts', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'failed_attempts' => $user->failed_login_attempts,
                    'blocked_until' => $user->blocked_until,
                    'block_duration_minutes' => User::BLOCK_DURATION_MINUTES,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            } else {
                // Log regular failed attempt with remaining attempts
                $remainingAttempts = User::MAX_FAILED_ATTEMPTS - $user->failed_login_attempts;

                Log::warning('Failed login attempt', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'failed_attempts' => $user->failed_login_attempts,
                    'remaining_attempts' => $remainingAttempts,
                    'account_status' => $user->account_status,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'warning' => $remainingAttempts === 1 ? 'LAST ATTEMPT BEFORE BLOCK' : null,
                ]);
            }
        } else {
            // ENHANCED: Log attempt for non-existent user (potential attack)
            Log::warning('Failed login attempt for non-existent user - POTENTIAL ATTACK', [
                'identifier' => $identifier,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Handle account lockout event.
     */
    public function handleLockout(Lockout $event): void
    {
        $request = $event->request;

        Log::warning('Account lockout triggered', [
            'throttle_key' => $request->input('email') ?? $request->input('username'),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle password reset event.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;

        if ($user && $user instanceof User) {
            $user->update([
                'password_changed_at' => now(),
                'password_changed_by' => 'self',
                'password_change_count' => ($user->password_change_count ?? 0) + 1,
            ]);

            $user->logActivity(
                'password_reset',
                'Password was reset via reset link',
                [
                    'change_count' => $user->password_change_count,
                ]
            );

            Log::info('User password reset', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            Attempting::class => 'handleAttempting',
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
