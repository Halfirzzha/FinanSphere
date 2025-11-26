<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Get the form schema for the login form
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Override login form component to accept username or email
     */
    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username or Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->placeholder('Enter your username or email address')
            ->helperText('You can login with either username or email')
            ->prefixIcon('heroicon-o-user-circle')
            ->maxLength(255);
    }

    /**
     * Override password component for better UI
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->placeholder('Enter your password')
            ->helperText('Your password is encrypted and secure')
            ->prefixIcon('heroicon-o-lock-closed')
            ->maxLength(255);
    }

    /**
     * Get credentials from form data
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'] ?? null;

        // Determine if login is email or username
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => $login,
            'password' => $data['password'],
        ];
    }

    /**
     * Throw failure validation exception
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'These credentials do not match our records.',
        ]);
    }
}
