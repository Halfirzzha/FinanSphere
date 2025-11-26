<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Register extends BaseRegister
{
    /**
     * Get the form for registration
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account Credentials')
                    ->description('Create your login credentials')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(User::class)
                            ->minLength(3)
                            ->maxLength(50)
                            ->alphaDash()
                            ->autocomplete('username')
                            ->placeholder('johndoe123')
                            ->helperText('Unique username for login (3-50 characters, letters, numbers, dashes, underscores)')
                            ->prefixIcon('heroicon-o-user-circle')
                            ->validationAttribute('username')
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(User::class)
                            ->maxLength(255)
                            ->autocomplete('email')
                            ->placeholder('john.doe@example.com')
                            ->helperText('Your primary email for account recovery')
                            ->prefixIcon('heroicon-o-envelope')
                            ->validationAttribute('email address')
                            ->columnSpan(1),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->rule(Password::default()->min(8)->mixedCase()->numbers()->symbols())
                            ->same('passwordConfirmation')
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('••••••••')
                            ->helperText('Min 8 characters: uppercase, lowercase, numbers, symbols')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->validationAttribute('password')
                            ->columnSpan(1),

                        TextInput::make('passwordConfirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('••••••••')
                            ->helperText('Re-enter your password to confirm')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->dehydrated(false)
                            ->validationAttribute('password confirmation')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Personal Details')
                    ->description('Tell us about yourself')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name')
                            ->placeholder('John Michael Doe')
                            ->helperText('Your complete legal name')
                            ->prefixIcon('heroicon-o-identification')
                            ->validationAttribute('full name')
                            ->columnSpan(1),

                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->autocomplete('tel')
                            ->placeholder('+62 812 3456 7890')
                            ->helperText('Your contact number (optional)')
                            ->prefixIcon('heroicon-o-phone')
                            ->validationAttribute('phone number')
                            ->columnSpan(1),

                        DatePicker::make('birth_date')
                            ->label('Birth Date')
                            ->maxDate(now()->subYears(13))
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->placeholder('Select your birth date')
                            ->helperText('Must be at least 13 years old (optional)')
                            ->prefixIcon('heroicon-o-cake')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Profile Picture')
                    ->description('Upload your avatar (optional)')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar Image')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->maxSize(2048)
                            ->directory('avatars')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Upload your profile picture (Max 2MB, JPG/PNG/WebP, will be resized to 200x200)')
                            ->imagePreviewHeight('150')
                            ->uploadingMessage('Uploading avatar...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Handle registration
     */
    protected function handleRegistration(array $data): User
    {
        // Create user with all data
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'registered_by' => 'self',
            'email_verified_at' => null, // Will be verified via email
            'password_changed_at' => now(),
            'password_changed_by' => 'self',
            'is_active' => true,
        ]);

        // Log registration activity
        $user->logActivity(
            'user_registered',
            'User registered successfully via registration form',
            [
                'registration_method' => 'web',
                'has_avatar' => !empty($data['avatar']),
            ]
        );

        return $user;
    }
}
