<?php

namespace App\Models;

use App\Services\UserAgentService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'username',
        'full_name',
        'email',
        'email_verified_at',
        'phone_number',
        'birth_date',
        'password',
        'password_changed_at',
        'password_changed_by',
        'password_change_count',
        'registered_by',
        'registered_by_admin_id',
        'registration_notes',
        'last_login_at',
        'last_login_ip_private',
        'last_login_ip_public',
        'last_login_browser',
        'last_login_browser_version',
        'last_login_platform',
        'last_login_user_agent',
        'total_login_count',
        'current_ip_private',
        'current_ip_public',
        'current_browser',
        'current_browser_version',
        'current_platform',
        'current_user_agent',
        'avatar',
        'is_active',
        'is_locked',
        'locked_at',
        'locked_by',
        'locked_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'password_change_count' => 'integer',
            'last_login_at' => 'datetime',
            'total_login_count' => 'integer',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * Fill the model with an array of attributes.
     * Handle backward compatibility for 'name' attribute
     */
    public function fill(array $attributes)
    {
        // If 'name' is provided, map it to 'full_name'
        if (isset($attributes['name']) && !isset($attributes['full_name'])) {
            $attributes['full_name'] = $attributes['name'];
            unset($attributes['name']);
        }

        return parent::fill($attributes);
    }

    /**
     * Boot function to auto-generate UUID and required fields
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate UUID
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            // Auto-generate username from email if not provided
            if (empty($model->username) && !empty($model->email)) {
                $baseUsername = explode('@', $model->email)[0];
                $username = $baseUsername;
                $counter = 1;

                // Check for unique username
                while (static::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                $model->username = $username;
            }

            // Auto-generate full_name if still not provided
            if (empty($model->full_name) && !empty($model->email)) {
                $model->full_name = explode('@', $model->email)[0];
            }

            // Set default registered_by if not provided
            if (empty($model->registered_by)) {
                $model->registered_by = 'self';
            }
        });
    }

    /**
     * Accessor for 'name' attribute (backward compatibility)
     */
    public function getNameAttribute(): string
    {
        return $this->full_name ?? $this->username ?? $this->email;
    }

    /**
     * Mutator for 'name' attribute (backward compatibility)
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['full_name'] = $value;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Relationship: Admin who registered this user
     */
    public function registeredByAdmin()
    {
        return $this->belongsTo(User::class, 'registered_by_admin_id');
    }

    /**
     * Relationship: Activity logs
     */
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Log user activity
     */
    public function logActivity(string $type, ?string $description = null, ?array $data = null)
    {
        $info = UserAgentService::getFullInfo();

        return $this->activityLogs()->create([
            'activity_type' => $type,
            'activity_description' => $description,
            'activity_data' => $data,
            'ip_address_private' => $info['ip_private'],
            'ip_address_public' => $info['ip_public'],
            'browser' => $info['browser_name'],
            'browser_version' => $info['browser_version'],
            'platform' => $info['platform'],
            'user_agent' => $info['user_agent'],
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'referrer' => request()->header('referer'),
            'status_code' => http_response_code() ?: 200,
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Update login information
     */
    public function updateLoginInfo()
    {
        $info = UserAgentService::getFullInfo();

        $this->update([
            'last_login_at' => now(),
            'last_login_ip_private' => $info['ip_private'],
            'last_login_ip_public' => $info['ip_public'],
            'last_login_browser' => $info['browser_name'],
            'last_login_browser_version' => $info['browser_version'],
            'last_login_platform' => $info['platform'],
            'last_login_user_agent' => $info['user_agent'],
            'total_login_count' => $this->total_login_count + 1,
            'current_ip_private' => $info['ip_private'],
            'current_ip_public' => $info['ip_public'],
            'current_browser' => $info['browser_name'],
            'current_browser_version' => $info['browser_version'],
            'current_platform' => $info['platform'],
            'current_user_agent' => $info['user_agent'],
        ]);

        $this->logActivity('login', 'User logged in successfully');
    }

    /**
     * Check if user has logged in today
     */
    public function hasLoggedInToday(): bool
    {
        return $this->last_login_at && $this->last_login_at->isToday();
    }

    /**
     * Get full age
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return \Carbon\Carbon::parse($this->birth_date)->age;
    }

    /**
     * Scope: Active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_locked', false);
    }

    /**
     * Scope: Locked users
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }
}
