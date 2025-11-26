<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->logActivity(
            'user_created',
            "User account created via {$user->registered_by}",
            [
                'registered_by' => $user->registered_by,
                'registered_by_admin_id' => $user->registered_by_admin_id,
            ]
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        // Ignore some automatic updates
        $ignoredFields = ['updated_at', 'last_login_at', 'total_login_count', 'current_ip_private', 'current_ip_public', 'current_browser', 'current_browser_version', 'current_platform', 'current_user_agent'];
        $changes = array_diff_key($changes, array_flip($ignoredFields));

        if (!empty($changes)) {
            // Check for password change
            if (isset($changes['password'])) {
                $user->logActivity(
                    'password_changed',
                    'User password was changed',
                    [
                        'changed_by' => $user->password_changed_by,
                        'change_count' => $user->password_change_count,
                    ]
                );
                unset($changes['password']);
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
                    ]
                );
            }

            // Log other profile updates
            if (!empty($changes)) {
                $user->logActivity(
                    'profile_updated',
                    'User profile information was updated',
                    [
                        'changed_fields' => array_keys($changes),
                    ]
                );
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $user->logActivity(
            'user_deleted',
            'User account was deleted (soft delete)',
            []
        );
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $user->logActivity(
            'user_restored',
            'User account was restored',
            []
        );
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        // Cannot log activity as the user is permanently deleted
    }
}
