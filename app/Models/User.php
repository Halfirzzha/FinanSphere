<?php

namespace App\Models;

use App\Services\UserAgentService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser, HasAvatar
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Constants for account status
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_TERMINATED = 'terminated';

    /**
     * Constants for security settings
     */
    public const MAX_FAILED_ATTEMPTS = 3;
    public const BLOCK_DURATION_MINUTES = 30;
    public const PASSWORD_EXPIRY_DAYS = 90;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'username',
        'full_name',
        'position',
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
        'first_login_at',
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
        'failed_login_attempts',
        'account_status',
        'is_active',
        'is_locked',
        'locked_at',
        'locked_by',
        'locked_reason',
        'blocked_by',
        'blocked_until',
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
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'total_login_count' => 'integer',
            'birth_date' => 'date',
            'failed_login_attempts' => 'integer',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'blocked_until' => 'datetime',
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
            // Auto-generate UUID (uppercase)
            if (empty($model->uuid)) {
                $model->uuid = strtoupper((string) Str::uuid());
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

            // Set default account status
            if (empty($model->account_status)) {
                $model->account_status = self::STATUS_ACTIVE;
            }

            // Initialize security counters
            if (is_null($model->failed_login_attempts)) {
                $model->failed_login_attempts = 0;
            }

            // Auto-set password_changed_at on first creation
            if (!empty($model->password) && empty($model->password_changed_at)) {
                $model->password_changed_at = now();
                $model->password_changed_by = $model->registered_by;
            }
        });

        // Auto-increment password_change_count on password update
        static::updating(function ($model) {
            if ($model->isDirty('password')) {
                $model->password_changed_at = now();
                $model->password_change_count = ($model->password_change_count ?? 0) + 1;

                if (empty($model->password_changed_by)) {
                    $model->password_changed_by = 'self';
                }
            }

            // Auto-unlock if blocked_until has passed
            if ($model->account_status === self::STATUS_BLOCKED &&
                $model->blocked_until &&
                now()->greaterThan($model->blocked_until)) {
                $model->account_status = self::STATUS_ACTIVE;
                $model->failed_login_attempts = 0;
                $model->blocked_until = null;
                $model->locked_reason = null;
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
     * Relationship: Admin who blocked this user
     */
    public function blockedByAdmin()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Relationship: Activity logs
     */
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relationship: Activities performed by this user (as admin)
     */
    public function performedActivities()
    {
        return $this->hasMany(UserActivityLog::class, 'performed_by')->orderBy('created_at', 'desc');
    }

    /**
     * Get the URL for the user's avatar for Filament
     * This method is automatically used by Filament for avatar display
     */
    public function getFilamentAvatarUrl(): ?string
    {
        // If avatar exists, return the storage URL
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Fallback to UI Avatars API with user initials
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Determine if the user can access the Filament panel
     * ENHANCED SECURITY: Multi-layer validation with auto-unlock
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Auto-unlock if block period has expired
        if ($this->account_status === self::STATUS_BLOCKED &&
            $this->blocked_until &&
            now()->greaterThan($this->blocked_until)) {

            $this->update([
                'account_status' => self::STATUS_ACTIVE,
                'failed_login_attempts' => 0,
                'blocked_until' => null,
                'locked_reason' => null,
                'locked_at' => null,
                'locked_by' => null,
            ]);

            $this->logActivity(
                'account_auto_unlocked',
                'Account automatically unlocked after block period expired',
                ['unlocked_at' => now()->toDateTimeString()]
            );

            // Refresh to get updated values
            $this->refresh();
        }

        // 2. STRICT MULTI-LAYER VALIDATION
        return $this->is_active &&                           // Must be active
               !$this->is_locked &&                          // Must not be manually locked
               $this->account_status === self::STATUS_ACTIVE && // Must have active status
               !$this->isBlocked() &&                        // Must not be blocked
               !$this->isSuspended() &&                      // Must not be suspended
               !$this->isTerminated();                       // Must not be terminated
    }

    /**
     * Log user activity with comprehensive data
     * ENHANCED: Now captures additional forensic data
     */
    public function logActivity(
        string $type,
        ?string $description = null,
        ?array $data = null,
        ?User $performedBy = null,
        string $result = 'success',
        ?string $errorMessage = null
    ) {
        $info = UserAgentService::getFullInfo();
        $request = request();

        // ENHANCED: Add comprehensive context data
        $enhancedData = array_merge($data ?? [], [
            // Timing Information
            'timestamp' => now()->toDateTimeString(),
            'local_hour' => now()->hour,
            'is_business_hours' => $this->isBusinessHours(now()),

            // Security Flags
            'account_status_snapshot' => $this->account_status,
            'is_active_snapshot' => $this->is_active,
            'is_locked_snapshot' => $this->is_locked,
            'failed_attempts_snapshot' => $this->failed_login_attempts,

            // Request Details
            'request_method' => $request->method(),
            'csrf_present' => $request->hasHeader('X-CSRF-TOKEN'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        // AI-ENHANCED: Add anomaly detection for security events
        if (in_array($type, ['login', 'login_failed', 'account_blocked'])) {
            $anomalyFlags = $this->detectLoginAnomalies($info);
            if (!empty($anomalyFlags)) {
                $enhancedData['anomaly_detected'] = true;
                $enhancedData['anomaly_flags'] = $anomalyFlags;
                $enhancedData['risk_level'] = $this->calculateRiskLevel($anomalyFlags);
            }
        }

        return $this->activityLogs()->create([
            'activity_type' => $type,
            'activity_description' => $description,
            'activity_data' => $enhancedData,
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
            'performed_by' => $performedBy?->id,
            'action_result' => $result,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if current time is business hours (8 AM - 6 PM)
     */
    protected function isBusinessHours(Carbon $time): bool
    {
        $hour = $time->hour;
        return $hour >= 8 && $hour <= 18;
    }

    /**
     * Calculate risk level based on anomaly flags
     */
    protected function calculateRiskLevel(array $anomalyFlags): string
    {
        $flagCount = count($anomalyFlags);
        $criticalFlags = array_intersect($anomalyFlags, [
            'rapid_attempts_automated',
            'unusual_hour_pattern'
        ]);

        if (count($criticalFlags) > 0 || $flagCount >= 3) {
            return 'high';
        } elseif ($flagCount >= 2) {
            return 'medium';
        } elseif ($flagCount >= 1) {
            return 'low';
        }

        return 'none';
    }

    /**
     * Update login information with automatic tracking
     */
    public function updateLoginInfo()
    {
        $info = UserAgentService::getFullInfo();
        $isFirstLogin = is_null($this->first_login_at);

        // Prepare update data
        $updateData = [
            'last_login_at' => now(),
            'last_login_ip_private' => $info['ip_private'],
            'last_login_ip_public' => $info['ip_public'],
            'last_login_browser' => $info['browser_name'],
            'last_login_browser_version' => $info['browser_version'],
            'last_login_platform' => $info['platform'],
            'last_login_user_agent' => $info['user_agent'],
            'current_ip_private' => $info['ip_private'],
            'current_ip_public' => $info['ip_public'],
            'current_browser' => $info['browser_name'],
            'current_browser_version' => $info['browser_version'],
            'current_platform' => $info['platform'],
            'current_user_agent' => $info['user_agent'],
            'failed_login_attempts' => 0, // Reset on successful login
            'account_status' => self::STATUS_ACTIVE, // Reactivate if was blocked
        ];

        // Set first login time if this is the first login
        if ($isFirstLogin) {
            $updateData['first_login_at'] = now();
        }

        // Clear temporary block data on successful login
        if ($this->account_status === self::STATUS_BLOCKED) {
            $updateData['blocked_until'] = null;
            $updateData['locked_reason'] = null;
        }

        // Perform update with raw SQL for login count increment
        $this->update($updateData);

        // Increment total_login_count separately using increment method
        $this->increment('total_login_count');

        // Refresh model to get updated values
        $this->refresh();

        // Log the activity
        $logMessage = $isFirstLogin
            ? 'User logged in successfully for the first time'
            : 'User logged in successfully';

        $this->logActivity('login', $logMessage, [
            'login_count' => $this->total_login_count,
            'is_first_login' => $isFirstLogin,
            'device_type' => $info['device_type'],
            'is_mobile' => $info['is_mobile'],
        ]);
    }

    /**
     * Handle failed login attempt with auto-blocking system
     * ENHANCED: AI-powered anomaly detection with double-counting prevention
     */
    public function handleFailedLogin(bool $skipIncrement = false)
    {
        // CRITICAL: Prevent double counting in same request
        $lockKey = "login_attempt_lock:{$this->id}:" . request()->ip();

        // Check if already processed in this request (within 2 seconds)
        if (Cache::has($lockKey)) {
            Log::debug('Duplicate handleFailedLogin call prevented', [
                'user_id' => $this->id,
                'ip' => request()->ip(),
            ]);
            return; // Skip duplicate processing
        }

        // Set lock for 2 seconds to prevent duplicate processing
        Cache::put($lockKey, true, 2);

        // Prevent double counting from multiple event triggers
        if (!$skipIncrement) {
            $this->increment('failed_login_attempts');
            $this->refresh();
        }

        $info = UserAgentService::getFullInfo();

        // AI-ENHANCED: Detect suspicious patterns
        $anomalyFlags = $this->detectLoginAnomalies($info);
        $isSuspicious = !empty($anomalyFlags);

        // Auto-block after max failed attempts
        if ($this->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $blockedUntil = now()->addMinutes(self::BLOCK_DURATION_MINUTES);

            // ENHANCED: Increase block duration for suspicious activity
            if ($isSuspicious) {
                $blockedUntil = now()->addMinutes(self::BLOCK_DURATION_MINUTES * 2); // 60 minutes for suspicious
            }

            // Generate professional lock reason
            $lockReason = $this->generateLockReason($isSuspicious, $anomalyFlags);

            $this->update([
                'account_status' => self::STATUS_BLOCKED,
                'locked_at' => now(),
                'locked_by' => 'system',
                'locked_reason' => $lockReason,
                'blocked_until' => $blockedUntil,
            ]);

            $this->logActivity(
                'account_blocked',
                "Account automatically blocked due to {$this->failed_login_attempts} failed login attempts",
                [
                    'failed_attempts' => $this->failed_login_attempts,
                    'blocked_until' => $blockedUntil->toDateTimeString(),
                    'blocked_duration_minutes' => $blockedUntil->diffInMinutes(now()),
                    'anomaly_detected' => $isSuspicious,
                    'anomaly_flags' => $anomalyFlags,
                    'current_ip' => request()->ip(),
                    'current_browser' => $info['browser_name'] ?? 'Unknown',
                    'current_platform' => $info['platform'] ?? 'Unknown',
                ],
                null,
                'success'
            );
        } else {
            $this->logActivity(
                'login_failed',
                "Failed login attempt ({$this->failed_login_attempts}/" . self::MAX_FAILED_ATTEMPTS . ")",
                [
                    'failed_attempts' => $this->failed_login_attempts,
                    'remaining_attempts' => self::MAX_FAILED_ATTEMPTS - $this->failed_login_attempts,
                    'anomaly_detected' => $isSuspicious,
                    'anomaly_flags' => $anomalyFlags,
                    'current_ip' => request()->ip(),
                ],
                null,
                'failed'
            );
        }
    }

    /**
     * AI-ENHANCED: Detect login anomalies
     * Returns array of detected anomaly flags
     */
    protected function detectLoginAnomalies(array $currentInfo): array
    {
        $anomalies = [];

        // Only check if user has previous login data
        if (!$this->last_login_at) {
            return $anomalies;
        }

        // 1. Check for different IP address (significant change)
        $currentIp = request()->ip();
        if ($this->last_login_ip_public && $currentIp !== $this->last_login_ip_public) {
            // Check if IPs are from same subnet (tolerate minor changes)
            $lastIpParts = explode('.', $this->last_login_ip_public);
            $currentIpParts = explode('.', $currentIp);

            // If first 2 octets differ, it's a different location
            if (count($lastIpParts) >= 2 && count($currentIpParts) >= 2) {
                if ($lastIpParts[0] !== $currentIpParts[0] || $lastIpParts[1] !== $currentIpParts[1]) {
                    $anomalies[] = 'ip_change_significant';
                }
            }
        }

        // 2. Check for different browser
        if ($this->last_login_browser &&
            isset($currentInfo['browser_name']) &&
            $currentInfo['browser_name'] !== $this->last_login_browser) {
            $anomalies[] = 'browser_change';
        }

        // 3. Check for different platform/OS
        if ($this->last_login_platform &&
            isset($currentInfo['platform']) &&
            $currentInfo['platform'] !== $this->last_login_platform) {
            $anomalies[] = 'platform_change';
        }

        // 4. Check for unusual time pattern (rapid attempts)
        if ($this->failed_login_attempts > 0 && $this->locked_at) {
            $secondsSinceLastAttempt = now()->diffInSeconds($this->locked_at);

            // If attempts are less than 5 seconds apart, it's automated
            if ($secondsSinceLastAttempt < 5) {
                $anomalies[] = 'rapid_attempts_automated';
            }
        }

        // 5. Check for unusual hour (login attempts at 2-5 AM local time is suspicious)
        $currentHour = now()->hour;
        if ($currentHour >= 2 && $currentHour <= 5) {
            // Only flag if user never logged in at these hours before
            if ($this->last_login_at && $this->last_login_at->hour >= 6 && $this->last_login_at->hour <= 23) {
                $anomalies[] = 'unusual_hour_pattern';
            }
        }

        // 6. Check device type change (desktop to mobile or vice versa)
        if ($this->last_login_at) {
            $lastDeviceType = $this->current_user_agent ?
                (strpos(strtolower($this->current_user_agent), 'mobile') !== false ? 'mobile' : 'desktop') :
                'unknown';
            $currentDeviceType = $currentInfo['is_mobile'] ? 'mobile' : 'desktop';

            if ($lastDeviceType !== 'unknown' && $lastDeviceType !== $currentDeviceType) {
                $anomalies[] = 'device_type_change';
            }
        }

        return $anomalies;
    }

    /**
     * Generate professional lock reason message with detailed context
     */
    protected function generateLockReason(bool $isSuspicious, array $anomalyFlags): string
    {
        $baseMessage = "Security Protocol: Account automatically secured after " . self::MAX_FAILED_ATTEMPTS . " consecutive failed authentication attempts";

        if ($isSuspicious && !empty($anomalyFlags)) {
            $suspiciousPatterns = [];

            if (in_array('ip_change_significant', $anomalyFlags)) {
                $suspiciousPatterns[] = 'unusual IP address';
            }
            if (in_array('browser_change', $anomalyFlags)) {
                $suspiciousPatterns[] = 'different browser';
            }
            if (in_array('platform_change', $anomalyFlags)) {
                $suspiciousPatterns[] = 'different device/platform';
            }
            if (in_array('rapid_attempts_automated', $anomalyFlags)) {
                $suspiciousPatterns[] = 'automated rapid attempts';
            }
            if (in_array('unusual_hour_pattern', $anomalyFlags)) {
                $suspiciousPatterns[] = 'unusual time pattern';
            }
            if (in_array('device_type_change', $anomalyFlags)) {
                $suspiciousPatterns[] = 'different device type';
            }

            if (!empty($suspiciousPatterns)) {
                $baseMessage .= " | Suspicious Activity Detected: " . implode(', ', $suspiciousPatterns);
            }
        }

        return $baseMessage;
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedAttempts()
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_reason' => null,
        ]);
    }

    /**
     * Block user account manually (by admin)
     */
    public function blockAccount(User $admin, string $reason, ?Carbon $until = null)
    {
        $this->update([
            'account_status' => self::STATUS_BLOCKED,
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $admin->id,
            'locked_reason' => $reason,
            'blocked_by' => $admin->id,
            'blocked_until' => $until,
        ]);

        $this->logActivity(
            'account_blocked_by_admin',
            "Account blocked by admin: {$reason}",
            [
                'blocked_by_user' => $admin->full_name,
                'blocked_by_id' => $admin->id,
                'blocked_until' => $until?->toDateTimeString(),
            ],
            $admin
        );
    }

    /**
     * Suspend user account
     */
    public function suspendAccount(User $admin, string $reason)
    {
        $this->update([
            'account_status' => self::STATUS_SUSPENDED,
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $admin->id,
            'locked_reason' => $reason,
            'blocked_by' => $admin->id,
        ]);

        $this->logActivity(
            'account_suspended',
            "Account suspended by admin: {$reason}",
            [
                'suspended_by_user' => $admin->full_name,
                'suspended_by_id' => $admin->id,
            ],
            $admin
        );
    }

    /**
     * Terminate user account
     */
    public function terminateAccount(User $admin, string $reason)
    {
        $this->update([
            'account_status' => self::STATUS_TERMINATED,
            'is_active' => false,
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $admin->id,
            'locked_reason' => $reason,
            'blocked_by' => $admin->id,
        ]);

        $this->logActivity(
            'account_terminated',
            "Account terminated by admin: {$reason}",
            [
                'terminated_by_user' => $admin->full_name,
                'terminated_by_id' => $admin->id,
            ],
            $admin
        );
    }

    /**
     * Unblock/reactivate user account
     */
    public function unblockAccount(User $admin, string $reason = 'Account reactivated by admin')
    {
        $this->update([
            'account_status' => self::STATUS_ACTIVE,
            'is_locked' => false,
            'is_active' => true,
            'locked_at' => null,
            'locked_by' => null,
            'locked_reason' => null,
            'blocked_by' => null,
            'blocked_until' => null,
            'failed_login_attempts' => 0,
        ]);

        $this->logActivity(
            'account_unblocked',
            $reason,
            [
                'unblocked_by_user' => $admin->full_name,
                'unblocked_by_id' => $admin->id,
            ],
            $admin
        );
    }

    /**
     * Check if user has logged in today
     */
    public function hasLoggedInToday(): bool
    {
        return $this->last_login_at && $this->last_login_at->isToday();
    }

    /**
     * Check if account is blocked
     * ENHANCED: Auto-unlock with comprehensive logging
     */
    public function isBlocked(): bool
    {
        // Auto-check if temporary block has expired
        if ($this->account_status === self::STATUS_BLOCKED &&
            $this->blocked_until &&
            now()->greaterThan($this->blocked_until)) {

            $this->update([
                'account_status' => self::STATUS_ACTIVE,
                'failed_login_attempts' => 0,
                'blocked_until' => null,
                'locked_reason' => null,
                'locked_at' => null,
                'locked_by' => null,
            ]);

            $this->logActivity(
                'account_auto_unlocked',
                'Account automatically unlocked after block period expired',
                [
                    'unlocked_at' => now()->toDateTimeString(),
                    'previous_block_duration' => now()->diffInMinutes($this->blocked_until) . ' minutes',
                ]
            );

            return false;
        }

        return $this->account_status === self::STATUS_BLOCKED;
    }

    /**
     * Check if account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->account_status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if account is terminated
     */
    public function isTerminated(): bool
    {
        return $this->account_status === self::STATUS_TERMINATED;
    }

    /**
     * Check if account is accessible (can login)
     * ENHANCED: Comprehensive validation with all security layers
     */
    public function canLogin(): bool
    {
        // Auto-unlock if blocked period expired
        if ($this->isBlocked() && $this->blocked_until && now()->greaterThan($this->blocked_until)) {
            return true; // isBlocked() already handles auto-unlock
        }

        return $this->is_active &&
               !$this->is_locked &&
               $this->account_status === self::STATUS_ACTIVE &&
               !$this->isBlocked() &&
               !$this->isSuspended() &&
               !$this->isTerminated();
    }

    /**
     * Get detailed blocking information for error messages
     * Returns array with comprehensive block details
     */
    public function getBlockingDetails(): array
    {
        $details = [
            'is_blocked' => false,
            'account_status' => $this->account_status,
            'is_active' => $this->is_active,
            'is_locked' => $this->is_locked,
            'can_login' => $this->canLogin(),
        ];

        // If account is blocked/locked/suspended/terminated
        if (!$this->canLogin()) {
            $details['is_blocked'] = true;
            $details['blocked_at'] = $this->locked_at?->toDateTimeString();
            $details['blocked_until'] = $this->blocked_until?->toDateTimeString();
            $details['blocked_reason'] = $this->locked_reason;
            $details['failed_attempts'] = $this->failed_login_attempts;

            // Calculate remaining time for temporary blocks
            if ($this->blocked_until) {
                $details['remaining_minutes'] = now()->lessThan($this->blocked_until) ?
                    now()->diffInMinutes($this->blocked_until) : 0;
            }

            // Get admin info if blocked by admin
            if ($this->blocked_by) {
                $admin = User::find($this->blocked_by);
                if ($admin) {
                    $details['blocked_by_admin'] = [
                        'id' => $admin->id,
                        'name' => $admin->full_name,
                        'position' => $admin->position,
                        'email' => $admin->email,
                    ];
                }
            } elseif ($this->locked_by === 'system') {
                $details['blocked_by_system'] = true;
                $details['block_type'] = 'automatic';
            }

            // Provide user-friendly message
            $details['message'] = $this->getBlockingMessage();
        }

        return $details;
    }

    /**
     * Get user-friendly blocking message
     */
    protected function getBlockingMessage(): string
    {
        if ($this->account_status === self::STATUS_BLOCKED) {
            if ($this->blocked_until && now()->lessThan($this->blocked_until)) {
                $minutes = now()->diffInMinutes($this->blocked_until);
                return "Your account is temporarily blocked. It will be automatically unlocked in {$minutes} minutes.";
            }
            return "Your account has been blocked. Please contact the administrator.";
        }

        if ($this->account_status === self::STATUS_SUSPENDED) {
            return "Your account has been suspended by an administrator. Please contact support for assistance.";
        }

        if ($this->account_status === self::STATUS_TERMINATED) {
            return "Your account has been terminated. Access is permanently restricted.";
        }

        if (!$this->is_active) {
            return "Your account has been deactivated. Please contact the administrator.";
        }

        if ($this->is_locked) {
            return "Your account has been locked by an administrator. Please contact support.";
        }

        return "Your account access is restricted. Please contact the administrator for assistance.";
    }

    /**
     * Check if password needs to be changed (expired)
     */
    public function needsPasswordChange(): bool
    {
        if (!$this->password_changed_at) {
            return false;
        }

        $daysSinceChange = now()->diffInDays($this->password_changed_at);
        return $daysSinceChange >= self::PASSWORD_EXPIRY_DAYS;
    }

    /**
     * Change password securely
     */
    public function changePassword(string $newPassword, ?User $changedBy = null)
    {
        $this->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'password_changed_by' => $changedBy ? $changedBy->id : 'self',
            'password_change_count' => $this->password_change_count + 1,
        ]);

        $this->logActivity(
            'password_changed',
            'Password was changed',
            [
                'changed_by' => $changedBy ? $changedBy->full_name : 'self',
                'change_count' => $this->password_change_count,
            ],
            $changedBy
        );
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
        return $query->where('is_active', true)
                    ->where('is_locked', false)
                    ->where('account_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Locked users
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope: Blocked users
     */
    public function scopeBlocked($query)
    {
        return $query->where('account_status', self::STATUS_BLOCKED);
    }

    /**
     * Scope: Suspended users
     */
    public function scopeSuspended($query)
    {
        return $query->where('account_status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope: Terminated users
     */
    public function scopeTerminated($query)
    {
        return $query->where('account_status', self::STATUS_TERMINATED);
    }

    /**
     * Scope: Users with expired passwords
     */
    public function scopePasswordExpired($query)
    {
        return $query->whereNotNull('password_changed_at')
                    ->whereRaw('DATEDIFF(NOW(), password_changed_at) >= ?', [self::PASSWORD_EXPIRY_DAYS]);
    }

    /**
     * Scope: Online users (logged in within last 15 minutes)
     */
    public function scopeOnline($query)
    {
        return $query->where('last_login_at', '>=', now()->subMinutes(15));
    }

    /**
     * Scope: Recently active users (logged in within last 24 hours)
     */
    public function scopeRecentlyActive($query)
    {
        return $query->where('last_login_at', '>=', now()->subHours(24));
    }
}
