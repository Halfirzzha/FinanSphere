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
                    ->description('Upload your profile photo')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Max 2MB. Recommended: 400x400px')
                            ->imagePreviewHeight('200')
                            ->alignCenter()
                            ->columnSpanFull(),
                    ])
                    ->compact(),

                Section::make('Personal Information')
                    ->description('Update your personal details')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('username')
                            ->label('Username')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Username cannot be changed')
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+62 812 3456 7890')
                            ->columnSpan(1),

                        DatePicker::make('birth_date')
                            ->label('Birth Date')
                            ->maxDate(now()->subYears(13))
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Change Password')
                    ->description('Leave blank to keep your current password')
                    ->schema([
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->rule(Password::default()->min(8)->mixedCase()->numbers()->symbols())
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->helperText('Min 8 characters with uppercase, lowercase, numbers & symbols')
                            ->columnSpan(1),

                        TextInput::make('passwordConfirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->same('password')
                            ->revealable()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
