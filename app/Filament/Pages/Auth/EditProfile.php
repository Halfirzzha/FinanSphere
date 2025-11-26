<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Picture')
                    ->description('Your avatar image')
                    ->aside()
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('')
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
                            ->helperText('Max 2MB. JPG/PNG/WebP. Resized to 200x200')
                            ->imagePreviewHeight('200')
                            ->avatar()
                            ->alignCenter(),
                    ]),

                Section::make('Account Credentials')
                    ->description('Your login information')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(3)
                            ->maxLength(50)
                            ->alphaDash()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Username cannot be changed after registration')
                            ->prefixIcon('heroicon-o-user-circle')
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('your.email@example.com')
                            ->helperText('Your primary email for account recovery')
                            ->prefixIcon('heroicon-o-envelope')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Personal Details')
                    ->description('Your personal information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('John Michael Doe')
                            ->helperText('Your complete legal name')
                            ->prefixIcon('heroicon-o-identification')
                            ->columnSpan(1),

                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+62 812 3456 7890')
                            ->helperText('Your contact number (optional)')
                            ->prefixIcon('heroicon-o-phone')
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

                Section::make('Change Password')
                    ->description('Update your password (leave blank to keep current)')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->rule(Password::default()->min(8)->mixedCase()->numbers()->symbols())
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->placeholder('••••••••')
                            ->helperText('Min 8 characters: uppercase, lowercase, numbers, symbols')
                            ->prefixIcon('heroicon-o-key')
                            ->columnSpan(1),

                        TextInput::make('passwordConfirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->same('password')
                            ->revealable()
                            ->placeholder('••••••••')
                            ->helperText('Re-enter your new password')
                            ->prefixIcon('heroicon-o-key')
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
