<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::default()
                    ->min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/',
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:' . now()->subYears(13)->format('Y-m-d'),
            ],
            'avatar' => [
                'nullable',
                'image',
                'max:2048',
                'mimes:jpeg,png,jpg,webp',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Username can only contain letters, numbers, hyphens, and underscores.',
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email address is already registered.',
            'password.uncompromised' => 'This password has been compromised in a data breach. Please choose a different password.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'birth_date.before' => 'You must be at least 13 years old to register.',
            'avatar.dimensions' => 'Avatar must be between 100x100 and 2000x2000 pixels.',
        ];
    }
}
