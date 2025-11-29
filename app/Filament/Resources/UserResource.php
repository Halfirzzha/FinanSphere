<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $navigationGroup = 'Filament Shield';

    protected static ?int $navigationSort = 1;

    /**
     * Shield: Control navigation visibility based on permission
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Shield: Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user && $user->can('view_any_user');
    }

    /**
     * Shield: Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user && $user->can('create_user');
    }

    /**
     * Shield: Check if user can edit specific record
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->can('update_user');
    }

    /**
     * Shield: Check if user can view specific record
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->can('view_user');
    }

    /**
     * Shield: Check if user can delete specific record
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->can('delete_user');
    }

    /**
     * Shield: Check if user can delete any records
     */
    public static function canDeleteAny(): bool
    {
        $user = Auth::user();

        return $user && $user->can('delete_any_user');
    }

    /**
     * Shield: Check if user can force delete
     */
    public static function canForceDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->can('force_delete_user');
    }

    /**
     * Shield: Check if user can restore
     */
    public static function canRestore(Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->can('restore_user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('User Information')
                    ->tabs([

                        // TAB 1: ACCOUNT INFORMATION
                        Tabs\Tab::make('Account Information')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Split::make([
                                    // Left Column
                                    Section::make('Basic Information')
                                        ->description('Core account details and credentials')
                                        ->icon('heroicon-o-identification')
                                        ->schema([
                                            // UUID - Read Only
                                            Forms\Components\TextInput::make('uuid')
                                                ->label('UUID')
                                                ->placeholder('Auto-generated on creation')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->maxLength(36)
                                                ->helperText('Unique identifier (system-managed)')
                                                ->suffixIcon('heroicon-o-finger-print')
                                                ->hidden(fn (string $context): bool => $context === 'create'),

                                            // Username
                                            Forms\Components\TextInput::make('username')
                                                ->label('Username')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(50)
                                                ->minLength(3)
                                                ->placeholder('Enter unique username')
                                                ->helperText('3-50 characters, alphanumeric with hyphens/underscores')
                                                ->prefixIcon('heroicon-o-at-symbol')
                                                ->autocomplete('username')
                                                ->columnSpan(1),

                                            // Email
                                            Forms\Components\TextInput::make('email')
                                                ->label('Email Address')
                                                ->email()
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(255)
                                                ->placeholder('user@example.com')
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->autocomplete('email')
                                                ->columnSpan(1),

                                            Grid::make(2)
                                                ->schema([
                                                    // Email Verified
                                                    Forms\Components\DateTimePicker::make('email_verified_at')
                                                        ->label('Email Verified At')
                                                        ->displayFormat('d M Y, H:i')
                                                        ->native(false)
                                                        ->suffixIcon('heroicon-o-check-circle')
                                                        ->helperText('Set verification time or leave empty'),

                                                    // Password
                                                    Forms\Components\TextInput::make('password')
                                                        ->label('Password')
                                                        ->password()
                                                        ->revealable()
                                                        ->required(fn (string $context): bool => $context === 'create')
                                                        ->dehydrated(fn ($state) => filled($state))
                                                        ->minLength(8)
                                                        ->maxLength(255)
                                                        ->placeholder('Enter secure password')
                                                        ->helperText('Min 8 characters (leave empty to keep current)'),
                                                ]),
                                        ])
                                        ->columnSpan(2),

                                    // Right Sidebar - Account Stats
                                    Section::make('Account Statistics')
                                        ->description('Key account metrics')
                                        ->icon('heroicon-o-chart-bar')
                                        ->schema([
                                            Placeholder::make('total_logins_display')
                                                ->label('Total Logins')
                                                ->content(fn (?User $record = null): string => $record ? number_format($record->total_login_count) : '0'
                                                )
                                                ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600']),

                                            Placeholder::make('failed_attempts_display')
                                                ->label('Failed Attempts')
                                                ->content(fn (?User $record = null): HtmlString|string => $record ? ($record->failed_login_attempts > 0
                                                        ? new HtmlString('<span class="text-danger-600 font-bold">'.$record->failed_login_attempts.'</span>')
                                                        : new HtmlString('<span class="text-success-600">0</span>'))
                                                    : '0'
                                                )
                                                ->extraAttributes(['class' => 'text-xl']),

                                            Placeholder::make('password_changes_display')
                                                ->label('Password Changes')
                                                ->content(fn (?User $record = null): string => $record ? number_format($record->password_change_count) : '0'
                                                ),

                                            Placeholder::make('account_age')
                                                ->label('Account Age')
                                                ->content(fn (?User $record = null): string => $record?->created_at ? $record->created_at->diffForHumans() : 'New'
                                                ),
                                        ])
                                        ->columnSpan(1)
                                        ->hidden(fn (string $context): bool => $context === 'create'),
                                ])->from('md'),
                            ]),

                        // TAB 2: PERSONAL INFORMATION
                        Tabs\Tab::make('Personal Information')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('Personal Details')
                                    ->description('User personal and contact information')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                // Full Name
                                                Forms\Components\TextInput::make('full_name')
                                                    ->label('Full Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Enter full name')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->autocomplete('name')
                                                    ->columnSpan(2),

                                                // Position
                                                Forms\Components\TextInput::make('position')
                                                    ->label('Position / Role')
                                                    ->maxLength(100)
                                                    ->placeholder('e.g., Manager, Developer')
                                                    ->prefixIcon('heroicon-o-briefcase')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                // Phone Number
                                                Forms\Components\TextInput::make('phone_number')
                                                    ->label('Phone Number')
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->placeholder('+1234567890')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\\/0-9]*$/')
                                                    ->helperText('International format recommended'),

                                                // Birth Date
                                                Forms\Components\DatePicker::make('birth_date')
                                                    ->label('Birth Date')
                                                    ->displayFormat('d M Y')
                                                    ->native(false)
                                                    ->suffixIcon('heroicon-o-cake')
                                                    ->maxDate(now()->subYears(13))
                                                    ->helperText('Must be at least 13 years old'),
                                            ]),

                                        // Avatar Upload
                                        Forms\Components\FileUpload::make('avatar')
                                            ->label('Profile Picture')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '1:1',
                                            ])
                                            ->directory('avatars')
                                            ->visibility('public')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->helperText('Max 2MB. Formats: JPG, PNG, WebP')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 3: SECURITY & STATUS
                        Tabs\Tab::make('Security & Status')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Account Status & Security')
                                    ->description('Security settings and account status management')
                                    ->icon('heroicon-o-lock-closed')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                // Account Status Select
                                                Forms\Components\Select::make('account_status')
                                                    ->label('Account Status')
                                                    ->required()
                                                    ->options([
                                                        'active' => 'Active',
                                                        'blocked' => 'Blocked',
                                                        'suspended' => 'Suspended',
                                                        'terminated' => 'Terminated',
                                                    ])
                                                    ->default('active')
                                                    ->native(false)
                                                    ->prefixIcon('heroicon-o-shield-check')
                                                    ->helperText('Current account access status'),

                                                // Is Active Toggle
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Active Status')
                                                    ->onIcon('heroicon-o-check-circle')
                                                    ->offIcon('heroicon-o-x-circle')
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->default(true)
                                                    ->helperText('Enable/disable account'),

                                                // Is Locked Toggle
                                                Forms\Components\Toggle::make('is_locked')
                                                    ->label('Locked')
                                                    ->onIcon('heroicon-o-lock-closed')
                                                    ->offIcon('heroicon-o-lock-open')
                                                    ->onColor('danger')
                                                    ->offColor('success')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->helperText('Manual lock by admin'),
                                            ]),

                                        // Conditional: Lock Details (only show when locked)
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('locked_at')
                                                    ->label('Locked At')
                                                    ->displayFormat('d M Y, H:i')
                                                    ->native(false)
                                                    ->suffixIcon('heroicon-o-calendar')
                                                    ->helperText('When account was locked'),

                                                Forms\Components\TextInput::make('locked_by')
                                                    ->label('Locked By')
                                                    ->maxLength(50)
                                                    ->placeholder('system|admin_id')
                                                    ->helperText('Who locked this account'),

                                            ])
                                            ->hidden(fn (Forms\Get $get): bool => ! $get('is_locked')),

                                        Forms\Components\Textarea::make('locked_reason')
                                            ->label('Lock/Block Reason')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Reason for locking/blocking this account...')
                                            ->helperText('Explain why this account was locked')
                                            ->columnSpanFull()
                                            ->hidden(fn (Forms\Get $get): bool => ! $get('is_locked')),

                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('failed_login_attempts')
                                                    ->label('Failed Login Attempts')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->suffixIcon('heroicon-o-exclamation-triangle')
                                                    ->helperText('Reset to 0 to unlock user'),

                                                Forms\Components\Select::make('blocked_by')
                                                    ->label('Blocked By Admin')
                                                    ->relationship('blockedByAdmin', 'full_name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Select admin who blocked'),

                                                Forms\Components\DateTimePicker::make('blocked_until')
                                                    ->label('Blocked Until')
                                                    ->displayFormat('d M Y, H:i')
                                                    ->native(false)
                                                    ->suffixIcon('heroicon-o-clock')
                                                    ->helperText('Temporary block expiry'),
                                            ]),
                                    ]),

                                Section::make('Password Management')
                                    ->description('Password history and change tracking')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('password_changed_at')
                                                    ->label('Last Password Change')
                                                    ->displayFormat('d M Y, H:i')
                                                    ->native(false)
                                                    ->helperText('Track when password was last changed'),

                                                Forms\Components\TextInput::make('password_changed_by')
                                                    ->label('Changed By')
                                                    ->maxLength(50)
                                                    ->placeholder('system|admin_id|self')
                                                    ->helperText('Track who changed the password'),

                                                Forms\Components\TextInput::make('password_change_count')
                                                    ->label('Total Changes')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->suffixIcon('heroicon-o-arrow-path')
                                                    ->helperText('Number of password changes'),

                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // TAB 4: REGISTRATION INFO
                        Tabs\Tab::make('Registration Info')
                            ->icon('heroicon-o-document-plus')
                            ->badge(fn (?User $record = null): string => $record?->registered_by ? strtoupper($record->registered_by) : 'NEW'
                            )
                            ->schema([
                                Section::make('Registration Details')
                                    ->description('How and when the account was created')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('registered_by')
                                                    ->label('Registered By')
                                                    ->options([
                                                        'system' => 'System',
                                                        'admin' => 'Admin',
                                                        'self' => 'Self Registration',
                                                    ])
                                                    ->default('self')
                                                    ->required()
                                                    ->native(false)
                                                    ->prefixIcon('heroicon-o-user-plus')
                                                    ->reactive(),

                                                Forms\Components\Select::make('registered_by_admin_id')
                                                    ->label('Admin Who Registered')
                                                    ->relationship('registeredByAdmin', 'full_name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Select admin')
                                                    ->helperText('Required if registered by admin')
                                                    ->visible(fn (Forms\Get $get): bool => $get('registered_by') === 'admin'),
                                            ]),

                                        Forms\Components\Textarea::make('registration_notes')
                                            ->label('Registration Notes')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->placeholder('Additional notes about this registration...')
                                            ->helperText('Optional notes or comments')
                                            ->columnSpanFull(),

                                        // Timestamps (Read-Only)
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('created_at')
                                                    ->label('Account Created')
                                                    ->content(fn (?User $record = null): string => $record?->created_at
                                                            ? $record->created_at->format('d M Y, H:i').' ('.$record->created_at->diffForHumans().')'
                                                            : 'Not yet created'
                                                    ),

                                                Placeholder::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->content(fn (?User $record = null): string => $record?->updated_at
                                                            ? $record->updated_at->format('d M Y, H:i').' ('.$record->updated_at->diffForHumans().')'
                                                            : 'Not yet updated'
                                                    ),

                                                Placeholder::make('deleted_at')
                                                    ->label('Deleted At')
                                                    ->content(fn (?User $record = null): HtmlString|string => $record?->deleted_at
                                                            ? $record->deleted_at->format('d M Y, H:i')
                                                            : new HtmlString('<span class="text-success-600 font-semibold">Active</span>')
                                                    ),
                                            ])
                                            ->hidden(fn (string $context): bool => $context === 'create'),
                                    ]),
                            ]),

                        // TAB 5: LOGIN ACTIVITY
                        Tabs\Tab::make('Login Activity')
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->badge(fn (?User $record = null): string => $record?->total_login_count ? (string) $record->total_login_count : '0'
                            )
                            ->badgeColor('success')
                            ->schema([
                                Section::make('Login History')
                                    ->description('Comprehensive login tracking information')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('first_login_display')
                                                    ->label('First Login')
                                                    ->content(fn (?User $record = null): HtmlString|string => $record?->first_login_at
                                                            ? $record->first_login_at->format('d M Y, H:i')
                                                            : new HtmlString('<span class="text-gray-400">Never logged in</span>')
                                                    ),

                                                Placeholder::make('last_login_display')
                                                    ->label('Last Login')
                                                    ->content(fn (?User $record = null): HtmlString|string => $record?->last_login_at
                                                            ? $record->last_login_at->format('d M Y, H:i').' ('.$record->last_login_at->diffForHumans().')'
                                                            : new HtmlString('<span class="text-gray-400">Never logged in</span>')
                                                    ),

                                                Placeholder::make('total_login_display')
                                                    ->label('Total Logins')
                                                    ->content(fn (?User $record = null): HtmlString => new HtmlString('<span class="text-2xl font-bold text-primary-600">'.
                                                        ($record ? number_format($record->total_login_count) : '0').
                                                        '</span>')
                                                    ),
                                            ]),

                                        Forms\Components\TextInput::make('last_login_ip_public')
                                            ->label('Last Public IP')
                                            ->maxLength(45)
                                            ->prefixIcon('heroicon-o-globe-alt')
                                            ->placeholder('No login yet')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('last_login_ip_private')
                                            ->label('Last Private IP')
                                            ->maxLength(45)
                                            ->prefixIcon('heroicon-o-computer-desktop')
                                            ->placeholder('No login yet')
                                            ->columnSpan(1),

                                    ])
                                    ->columns(2)
                                    ->hidden(fn (string $context): bool => $context === 'create'),

                                Section::make('Last Login Device Information')
                                    ->description('Browser and platform details from last login')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('last_login_browser')
                                                    ->label('Browser')
                                                    ->maxLength(100)
                                                    ->prefixIcon('heroicon-o-globe-alt')
                                                    ->placeholder('N/A'),

                                                Forms\Components\TextInput::make('last_login_browser_version')
                                                    ->label('Browser Version')
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->placeholder('N/A'),

                                                Forms\Components\TextInput::make('last_login_platform')
                                                    ->label('Platform / OS')
                                                    ->maxLength(50)
                                                    ->prefixIcon('heroicon-o-computer-desktop')
                                                    ->placeholder('N/A'),

                                            ]),

                                        Forms\Components\Textarea::make('last_login_user_agent')
                                            ->label('User Agent String')
                                            ->rows(3)
                                            ->placeholder('No user agent data')
                                            ->columnSpanFull(),

                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->hidden(fn (string $context): bool => $context === 'create'),

                                Section::make('Current Session Information')
                                    ->description('Active session device details')
                                    ->icon('heroicon-o-signal')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('current_ip_public')
                                                    ->label('Current Public IP')
                                                    ->maxLength(45)
                                                    ->prefixIcon('heroicon-o-globe-alt'),

                                                Forms\Components\TextInput::make('current_ip_private')
                                                    ->label('Current Private IP')
                                                    ->maxLength(45)
                                                    ->prefixIcon('heroicon-o-computer-desktop'),

                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('current_browser')
                                                    ->label('Current Browser')
                                                    ->maxLength(100)
                                                    ->prefixIcon('heroicon-o-globe-alt'),

                                                Forms\Components\TextInput::make('current_browser_version')
                                                    ->label('Browser Version')
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-o-hashtag'),

                                                Forms\Components\TextInput::make('current_platform')
                                                    ->label('Current Platform')
                                                    ->maxLength(50)
                                                    ->prefixIcon('heroicon-o-computer-desktop'),

                                            ]),

                                        Forms\Components\Textarea::make('current_user_agent')
                                            ->label('Current User Agent')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->hidden(fn (string $context): bool => $context === 'create'),
                            ]),

                        // TAB 6: ROLES & PERMISSIONS
                        Tabs\Tab::make('Roles & Permissions')
                            ->icon('heroicon-o-shield-check')
                            ->badge(fn (?User $record = null): string => $record ? (string) $record->roles->count() : '0')
                            ->badgeColor('warning')
                            ->schema([
                                Section::make('Role Management')
                                    ->description('Assign and manage user roles. Super Admin has full control.')
                                    ->icon('heroicon-o-user-group')
                                    ->schema([
                                        Forms\Components\Select::make('roles')
                                            ->label('User Roles')
                                            ->multiple()
                                            ->relationship('roles', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Select one or more roles')
                                            ->helperText('Select roles to assign to this user. Multiple roles can be assigned.')
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->columnSpanFull()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set) {
                                                // Auto-update hint when roles change
                                                $set('roles_count', count($state ?? []));
                                            })
                                            ->hint(fn (?User $record = null): string => $record
                                                ? 'Current: '.$record->roles->pluck('name')->join(', ')
                                                : 'No roles assigned yet'),

                                        Forms\Components\Placeholder::make('roles_info')
                                            ->label('Available Roles')
                                            ->content(function () {
                                                $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
                                                $html = '<div class="space-y-2">';
                                                foreach ($roles as $role) {
                                                    $permCount = $role->permissions->count();
                                                    $color = match ($role->name) {
                                                        'super_admin' => 'text-red-600 font-bold',
                                                        'User' => 'text-blue-600',
                                                        default => 'text-gray-600'
                                                    };
                                                    $html .= "<div class='flex items-center gap-2'>";
                                                    $html .= "<span class='$color'>• {$role->name}</span>";
                                                    $html .= "<span class='text-xs text-gray-500'>({$permCount} permissions)</span>";
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';

                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull()
                                            ->hidden(fn (string $context): bool => $context !== 'create'),
                                    ]),

                                Section::make('Permission Summary')
                                    ->description('View permissions inherited from assigned roles')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        Forms\Components\Placeholder::make('permissions_summary')
                                            ->label('Effective Permissions')
                                            ->content(function (?User $record = null) {
                                                if (! $record) {
                                                    return new HtmlString('<span class="text-gray-400">Save user first to see permissions</span>');
                                                }

                                                $permissions = $record->getAllPermissions();

                                                if ($permissions->isEmpty()) {
                                                    return new HtmlString('<span class="text-gray-400">No permissions assigned</span>');
                                                }

                                                // Group permissions by type
                                                $grouped = $permissions->groupBy(function ($permission) {
                                                    if (str_contains($permission->name, 'category')) {
                                                        return 'Categories';
                                                    }
                                                    if (str_contains($permission->name, 'transaction')) {
                                                        return 'Transactions';
                                                    }
                                                    if (str_contains($permission->name, 'debt')) {
                                                        return 'Debts';
                                                    }
                                                    if (str_contains($permission->name, 'user')) {
                                                        return 'Users';
                                                    }
                                                    if (str_contains($permission->name, 'role')) {
                                                        return 'Roles';
                                                    }
                                                    if (str_contains($permission->name, 'widget')) {
                                                        return 'Widgets';
                                                    }
                                                    if (str_contains($permission->name, 'page')) {
                                                        return 'Pages';
                                                    }

                                                    return 'Other';
                                                });

                                                $html = '<div class="grid grid-cols-2 gap-4">';
                                                foreach ($grouped as $group => $perms) {
                                                    $html .= "<div class='border rounded-lg p-3 bg-gray-50'>";
                                                    $html .= "<h4 class='font-semibold text-sm mb-2 text-gray-700'>{$group} ({$perms->count()})</h4>";
                                                    $html .= "<ul class='space-y-1 text-xs text-gray-600'>";
                                                    foreach ($perms->take(5) as $perm) {
                                                        $html .= '<li>• '.str_replace('_', ' ', $perm->name).'</li>';
                                                    }
                                                    if ($perms->count() > 5) {
                                                        $html .= "<li class='text-gray-400'>... and ".($perms->count() - 5).' more</li>';
                                                    }
                                                    $html .= '</ul></div>';
                                                }
                                                $html .= '</div>';
                                                $html .= "<div class='mt-3 p-2 bg-blue-50 rounded text-sm text-blue-700'>";
                                                $html .= "Total Permissions: <strong>{$permissions->count()}</strong>";
                                                $html .= '</div>';

                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(fn (string $context): bool => $context === 'create')
                                    ->hidden(fn (string $context): bool => $context === 'create'),

                                Section::make('Role Assignment Notes')
                                    ->description('Important information about role management')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Forms\Components\Placeholder::make('role_notes')
                                            ->label('')
                                            ->content(new HtmlString('
                                                <div class="space-y-2 text-sm">
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-blue-600">ℹ️</span>
                                                        <span><strong>Auto-Assignment:</strong> New users automatically receive the "User" role during self-registration.</span>
                                                    </div>
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-green-600">✅</span>
                                                        <span><strong>Manual Override:</strong> Super Admins can change, add, or remove roles at any time.</span>
                                                    </div>
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-yellow-600">⚠️</span>
                                                        <span><strong>Multiple Roles:</strong> Users can have multiple roles. Permissions are cumulative.</span>
                                                    </div>
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-red-600">🔒</span>
                                                        <span><strong>Super Admin:</strong> The super_admin role bypasses all permission checks and has full system access.</span>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->activeTab(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Avatar
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name).'&color=7F9CF5&background=EBF4FF')
                    ->size(40),

                // Full Name
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Name copied!')
                    ->icon('heroicon-o-user'),

                // Username
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-at-symbol')
                    ->copyable(),

                // Email
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email copied!'),

                // Email Verified
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record): string => $record->email_verified_at
                            ? 'Verified on '.$record->email_verified_at->format('d M Y')
                            : 'Not verified'
                    ),

                // Position
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->searchable()
                    ->icon('heroicon-o-briefcase')
                    ->default('-')
                    ->badge()
                    ->color('info'),

                // Roles
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'User' => 'success',
                        default => 'info',
                    })
                    ->icon('heroicon-o-shield-check')
                    ->searchable()
                    ->tooltip('Click to view/edit roles'),

                // Account Status
                Tables\Columns\TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        'suspended' => 'warning',
                        'terminated' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'blocked' => 'heroicon-o-no-symbol',
                        'suspended' => 'heroicon-o-pause-circle',
                        'terminated' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                // Is Active
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn ($record): string => $record->is_active ? 'Active' : 'Inactive'),

                // Is Locked
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->tooltip(fn ($record): string => $record->is_locked ? 'Locked' : 'Unlocked'),

                // Total Logins
                Tables\Columns\TextColumn::make('total_login_count')
                    ->label('Logins')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state): string => number_format($state)),

                // Failed Attempts
                Tables\Columns\TextColumn::make('failed_login_attempts')
                    ->label('Failed Attempts')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state >= 3 => 'danger',
                        $state >= 1 => 'warning',
                        default => 'gray',
                    }),

                // Last Login
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->toggleable()
                    ->tooltip(fn ($record): string => $record->last_login_at
                            ? 'IP: '.($record->last_login_ip_public ?? 'N/A').' | Browser: '.($record->last_login_browser ?? 'Unknown')
                            : 'Never logged in'
                    ),

                // Registered By
                Tables\Columns\TextColumn::make('registered_by')
                    ->label('Registered By')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'system' => 'info',
                        'admin' => 'warning',
                        'self' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->toggleable(),

                // Created At
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable()
                    ->since(),

                // Updated At
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),

                // Deleted At
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Status Filter
                Tables\Filters\SelectFilter::make('account_status')
                    ->label('Account Status')
                    ->options([
                        'active' => 'Active',
                        'blocked' => 'Blocked',
                        'suspended' => 'Suspended',
                        'terminated' => 'Terminated',
                    ])
                    ->multiple()
                    ->indicator('Status'),

                // Active Filter
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->indicator('Active'),

                // Locked Filter
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Lock Status')
                    ->placeholder('All users')
                    ->trueLabel('Locked only')
                    ->falseLabel('Unlocked only')
                    ->indicator('Locked'),

                // Email Verified Filter
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    )
                    ->indicator('Email Verified'),

                // Registration Method Filter
                Tables\Filters\SelectFilter::make('registered_by')
                    ->label('Registered By')
                    ->options([
                        'system' => 'System',
                        'admin' => 'Admin',
                        'self' => 'Self',
                    ])
                    ->multiple()
                    ->indicator('Registration'),

                // Failed Login Attempts Filter
                Tables\Filters\Filter::make('high_failed_attempts')
                    ->label('High Failed Attempts')
                    ->query(fn (Builder $query): Builder => $query->where('failed_login_attempts', '>=', 3))
                    ->toggle()
                    ->indicator('High Failed Attempts'),

                // Recent Login Filter
                Tables\Filters\Filter::make('recent_login')
                    ->label('Logged in Last 7 Days')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(7)))
                    ->toggle()
                    ->indicator('Recent Login'),

                // Never Logged In Filter
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Never Logged In')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at'))
                    ->toggle()
                    ->indicator('Never Logged In'),

                // Soft Delete Filter
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Users')
                    ->placeholder('Without deleted')
                    ->trueLabel('With deleted')
                    ->falseLabel('Only deleted')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    // Custom: Verify Email Action
                    Tables\Actions\Action::make('verify_email')
                        ->label('Verify Email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->hidden(fn ($record): bool => $record->email_verified_at !== null)
                        ->action(fn ($record) => $record->update(['email_verified_at' => now()]))
                        ->successNotificationTitle('Email verified successfully'),

                    // Custom: Reset Failed Attempts
                    Tables\Actions\Action::make('reset_failed_attempts')
                        ->label('Reset Failed Attempts')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->hidden(fn ($record): bool => $record->failed_login_attempts === 0)
                        ->action(fn ($record) => $record->update([
                            'failed_login_attempts' => 0,
                            'blocked_until' => null,
                        ]))
                        ->successNotificationTitle('Failed attempts reset'),

                    // Custom: Lock/Unlock Account
                    Tables\Actions\Action::make('toggle_lock')
                        ->label(fn ($record): string => $record->is_locked ? 'Unlock' : 'Lock')
                        ->icon(fn ($record): string => $record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                        ->color(fn ($record): string => $record->is_locked ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'is_locked' => ! $record->is_locked,
                                'locked_at' => ! $record->is_locked ? now() : null,
                                'locked_by' => ! $record->is_locked ? Auth::id() : null,
                            ]);
                        })
                        ->successNotificationTitle(fn ($record): string => $record->is_locked ? 'Account locked' : 'Account unlocked'
                        ),

                    // Custom: Activate/Deactivate
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record): string => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record): string => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active]))
                        ->successNotificationTitle(fn ($record): string => $record->is_active ? 'Account activated' : 'Account deactivated'
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),

                    Tables\Actions\RestoreAction::make()
                        ->color('success'),

                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk: Activate
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Users activated'),

                    // Bulk: Deactivate
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Users deactivated'),

                    // Bulk: Reset Failed Attempts
                    Tables\Actions\BulkAction::make('reset_attempts')
                        ->label('Reset Failed Attempts')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update([
                            'failed_login_attempts' => 0,
                            'blocked_until' => null,
                        ]))
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Failed attempts reset'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),

                    Tables\Actions\RestoreBulkAction::make(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-horizontal'),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('Create First User'),
            ])
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('Create your first user to get started.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['roles', 'permissions']) // Eager load roles and permissions for better performance
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
