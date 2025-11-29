<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Auto-assign default 'User' role for new registrations
        // Only assign if user doesn't have any role yet (prevents overriding manual assignments)
        if (!$user->hasAnyRole()) {
            // Use 'User' (capitalized) to match Shield's default role naming
            $user->assignRole('User');
        }

        // Avoid logging during seeding or testing
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $user->logActivity(
            'user_created',
            "User account created via {$user->registered_by}",
            [
                'registered_by' => $user->registered_by,
                'registered_by_admin_id' => $user->registered_by_admin_id,
                'username' => $user->username,
                'email' => $user->email,
                'assigned_role' => 'User',
            ]
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Avoid logging during seeding or testing
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $changes = $user->getChanges();

        // Ignore automatic updates from login/session tracking
        $ignoredFields = [
            'updated_at',
            'last_login_at',
            'total_login_count',
            'current_ip_private',
            'current_ip_public',
            'current_browser',
            'current_browser_version',
            'current_platform',
            'current_user_agent',
            'remember_token',
            'failed_login_attempts',
        ];

        $changes = array_diff_key($changes, array_flip($ignoredFields));

        if (empty($changes)) {
            return;
        }

        // Get the admin who performed the action
        $performedBy = Auth::check() && Auth::id() !== $user->id ? Auth::user() : null;

        // Check for password change
        if (isset($changes['password'])) {
            $user->logActivity(
                'password_changed',
                'User password was changed',
                [
                    'changed_by' => $user->password_changed_by,
                    'change_count' => $user->password_change_count,
                ],
                $performedBy
            );
            unset($changes['password'], $changes['password_changed_at'], $changes['password_changed_by'], $changes['password_change_count']);
        }

        // Check for account status changes
        if (isset($changes['account_status'])) {
            $oldStatus = $user->getOriginal('account_status');
            $newStatus = $changes['account_status'];

            $statusMessages = [
                User::STATUS_BLOCKED => 'blocked',
                User::STATUS_SUSPENDED => 'suspended',
                User::STATUS_TERMINATED => 'terminated',
                User::STATUS_ACTIVE => 'activated',
            ];

            $action = $statusMessages[$newStatus] ?? 'status_changed';

            $user->logActivity(
                "account_{$action}",
                "Account status changed from {$oldStatus} to {$newStatus}",
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'locked_by' => $user->locked_by,
                    'locked_reason' => $user->locked_reason,
                ],
                $performedBy
            );

            unset($changes['account_status']);
        }

        // Check for lock status change
        if (isset($changes['is_locked'])) {
            $status = $changes['is_locked'] ? 'locked' : 'unlocked';
            $user->logActivity(
                "account_{$status}",
                "User account was {$status}",
                [
                    'locked_by' => $user->locked_by,
                    'locked_reason' => $user->locked_reason,
                    'locked_at' => $user->locked_at?->toDateTimeString(),
                ],
                $performedBy
            );
            unset($changes['is_locked'], $changes['locked_at'], $changes['locked_by'], $changes['locked_reason']);
        }

        // Check for activation/deactivation
        if (isset($changes['is_active'])) {
            $status = $changes['is_active'] ? 'activated' : 'deactivated';
            $user->logActivity(
                "account_{$status}",
                "User account was {$status}",
                [],
                $performedBy
            );
            unset($changes['is_active']);
        }

        // Check for email verification
        if (isset($changes['email_verified_at'])) {
            $user->logActivity(
                'email_verified',
                'User email was verified',
                [
                    'email' => $user->email,
                    'verified_at' => $user->email_verified_at?->toDateTimeString(),
                ],
                $performedBy
            );
            unset($changes['email_verified_at']);
        }

        // Log remaining profile updates
        if (!empty($changes)) {
            $changedFields = array_keys($changes);

            $user->logActivity(
                'profile_updated',
                'User profile information was updated',
                [
                    'changed_fields' => $changedFields,
                    'changes' => array_map(function ($field) use ($user, $changes) {
                        return [
                            'field' => $field,
                            'old' => $user->getOriginal($field),
                            'new' => $changes[$field],
                        ];
                    }, $changedFields),
                ],
                $performedBy
            );
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $performedBy = Auth::check() ? Auth::user() : null;

        $user->logActivity(
            'user_deleted',
            'User account was deleted (soft delete)',
            [
                'deleted_by' => $performedBy?->full_name ?? 'system',
            ],
            $performedBy
        );
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $performedBy = Auth::check() ? Auth::user() : null;

        $user->logActivity(
            'user_restored',
            'User account was restored from soft delete',
            [
                'restored_by' => $performedBy?->full_name ?? 'system',
            ],
            $performedBy
        );
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        // Cannot log activity as the user is permanently deleted
        // Activity logs will be cascade deleted by foreign key constraint
    }
}
