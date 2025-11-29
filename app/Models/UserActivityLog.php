<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    /**
     * Disable updated_at timestamp
     */
    public const UPDATED_AT = null;

    /**
     * Activity type constants
     */
    public const TYPE_LOGIN = 'login';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_LOGIN_FAILED = 'login_failed';
    public const TYPE_PASSWORD_CHANGED = 'password_changed';
    public const TYPE_PROFILE_UPDATED = 'profile_updated';
    public const TYPE_ACCOUNT_BLOCKED = 'account_blocked';
    public const TYPE_ACCOUNT_BLOCKED_BY_ADMIN = 'account_blocked_by_admin';
    public const TYPE_ACCOUNT_SUSPENDED = 'account_suspended';
    public const TYPE_ACCOUNT_TERMINATED = 'account_terminated';
    public const TYPE_ACCOUNT_UNBLOCKED = 'account_unblocked';
    public const TYPE_USER_CREATED = 'user_created';
    public const TYPE_USER_DELETED = 'user_deleted';
    public const TYPE_USER_RESTORED = 'user_restored';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_description',
        'activity_data',
        'ip_address_private',
        'ip_address_public',
        'browser',
        'browser_version',
        'platform',
        'user_agent',
        'method',
        'url',
        'referrer',
        'status_code',
        'session_id',
        'performed_by',
        'action_result',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_data' => 'array',
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: User who performed the action (admin)
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Today's activities
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Failed actions
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('action_result', ['failed', 'error']);
    }

    /**
     * Scope: Successful actions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('action_result', 'success');
    }

    /**
     * Scope: Security events
     */
    public function scopeSecurityEvents($query)
    {
        return $query->whereIn('activity_type', [
            self::TYPE_LOGIN,
            self::TYPE_LOGIN_FAILED,
            self::TYPE_ACCOUNT_BLOCKED,
            self::TYPE_ACCOUNT_SUSPENDED,
            self::TYPE_ACCOUNT_TERMINATED,
            self::TYPE_PASSWORD_CHANGED,
        ]);
    }

    /**
     * Scope: Admin actions
     */
    public function scopeAdminActions($query)
    {
        return $query->whereNotNull('performed_by');
    }

    /**
     * Scope: Filter by IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where(function ($q) use ($ip) {
            $q->where('ip_address_private', $ip)
              ->orWhere('ip_address_public', $ip);
        });
    }

    /**
     * Scope: Login attempts
     */
    public function scopeLoginAttempts($query)
    {
        return $query->whereIn('activity_type', [self::TYPE_LOGIN, self::TYPE_LOGIN_FAILED]);
    }
}
