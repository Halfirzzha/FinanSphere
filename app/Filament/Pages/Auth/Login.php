<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Login extends BaseLogin
{
    /**
     * Get the form schema for the login form
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Override login form component to accept username or email
     */
    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username or Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->placeholder('Enter your username or email address')
            ->helperText('You can login with either username or email')
            ->prefixIcon('heroicon-o-user-circle')
            ->maxLength(255);
    }

    /**
     * Override password component for better UI
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->placeholder('Enter your password')
            ->helperText('Your password is encrypted and secure')
            ->prefixIcon('heroicon-o-lock-closed')
            ->maxLength(255);
    }

    /**
     * Get credentials from form data
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'] ?? null;

        // Determine if login is email or username
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => $login,
            'password' => $data['password'],
        ];
    }

    /**
     * Throw failure validation exception
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * ENHANCED SECURITY: Pre-authentication validation
     * Validate account status BEFORE attempting authentication
     */
    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);

        // Find user first
        $login = $data['login'] ?? null;
        $user = User::where('email', $login)
                    ->orWhere('username', $login)
                    ->first();

        // PRE-AUTHENTICATION SECURITY CHECKS
        if ($user) {
            // 1. Check if blocked_until has expired and auto-unlock
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
                    'Account automatically unlocked after block period expired',
                    ['unlocked_at' => now()->toDateTimeString()]
                );
            }

            // Refresh user data after potential unlock
            $user->refresh();

            // 2. STRICT VALIDATION: Account Status
            if ($user->account_status !== User::STATUS_ACTIVE) {
                $this->throwAccountStatusException($user);
            }

            // 3. STRICT VALIDATION: Is Active
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'data.login' => 'Your account has been deactivated. Please contact the administrator for assistance.',
                ]);
            }

            // 4. STRICT VALIDATION: Is Locked (Manual Lock by Admin)
            if ($user->is_locked) {
                $this->throwLockedAccountException($user);
            }

            // 5. STRICT VALIDATION: Blocked Until Check (Double Safety)
            if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
                $this->throwBlockedAccountException($user);
            }
        }

        // Proceed with standard authentication
        return parent::authenticate();
    }

    /**
     * Throw exception for non-active account status with professional message
     */
    protected function throwAccountStatusException(User $user): never
    {
        $statusMessages = [
            User::STATUS_BLOCKED => 'Account Blocked',
            User::STATUS_SUSPENDED => 'Account Suspended',
            User::STATUS_TERMINATED => 'Account Terminated',
        ];

        $title = $statusMessages[$user->account_status] ?? 'Account Restricted';

        // Build detailed message
        $message = $this->buildDetailedBlockMessage($user, $title);

        throw ValidationException::withMessages([
            'data.login' => $message,
        ]);
    }

    /**
     * Throw exception for locked account
     */
    protected function throwLockedAccountException(User $user): never
    {
        $message = $this->buildDetailedBlockMessage($user, 'Account Locked');

        throw ValidationException::withMessages([
            'data.login' => $message,
        ]);
    }

    /**
     * Throw exception for blocked account (temporary)
     */
    protected function throwBlockedAccountException(User $user): never
    {
        $message = $this->buildDetailedBlockMessage($user, 'Temporary Block Active');

        throw ValidationException::withMessages([
            'data.login' => $message,
        ]);
    }

    /**
     * Build detailed professional block message with all context
     */
    protected function buildDetailedBlockMessage(User $user, string $title): string
    {
        $lines = [];
        $lines[] = $title;

        // Reason (extract clean message if too long)
        if ($user->locked_reason) {
            // Show clean reason without duplication
            $reason = $user->locked_reason;

            // If contains '|' separator (from suspicious activity), show full message
            if (strpos($reason, '|') !== false) {
                $lines[] = $reason;
            } else {
                // Extract main message only
                $cleanReason = str_replace('Security Protocol: ', '', $reason);
                $cleanReason = preg_replace('/Account automatically (secured|blocked) after \d+ consecutive failed authentication attempts/',
                    'Too many failed login attempts', $cleanReason);
                $lines[] = 'Reason: ' . $cleanReason;
            }
        }

        // Time information
        if ($user->blocked_until) {
            $unblockTime = Carbon::parse($user->blocked_until);
            $now = now();

            if ($now->lessThan($unblockTime)) {
                $remainingMinutes = (int) $now->diffInMinutes($unblockTime);
                $hours = floor($remainingMinutes / 60);
                $minutes = $remainingMinutes % 60;

                $timeDisplay = $hours > 0
                    ? "{$hours} hour(s) {$minutes} minute(s)"
                    : "{$minutes} minute(s)";

                $lines[] = 'Auto-unlock in: ' . $timeDisplay;
                $lines[] = 'Unlock time: ' . $unblockTime->format('d M Y, H:i');
            }
        }

        // Security context
        $lines[] = 'Security Policy: Maximum ' . User::MAX_FAILED_ATTEMPTS . ' failed attempts allowed';

        if ($user->failed_login_attempts > 0) {
            $lines[] = 'Your failed attempts: ' . $user->failed_login_attempts;
        }

        // Who blocked
        if ($user->locked_by === 'system') {
            $lines[] = 'Action: Automatic Security Lock';
        } elseif ($user->blocked_by) {
            $admin = User::find($user->blocked_by);
            if ($admin) {
                $lines[] = 'Blocked by: ' . $admin->full_name . ' (' . ($admin->position ?: 'Administrator') . ')';
            }
        }

        $lines[] = 'For assistance, contact your system administrator';

        return implode(' | ', $lines);
    }
}
