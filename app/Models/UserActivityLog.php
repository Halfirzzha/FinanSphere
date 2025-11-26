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
}
