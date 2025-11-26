<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $birthDate = fake()->dateTimeBetween('-60 years', '-18 years');

        return [
            'uuid' => (string) Str::uuid(),
            'username' => fake()->unique()->userName(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone_number' => fake()->phoneNumber(),
            'birth_date' => $birthDate->format('Y-m-d'),
            'registered_by' => fake()->randomElement(['system', 'admin', 'self']),
            'registered_by_admin_id' => null,
            'registration_notes' => fake()->optional()->sentence(),
            'password_changed_at' => now(),
            'password_changed_by' => 'system',
            'password_change_count' => 0,
            'is_active' => true,
            'is_locked' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => 'admin',
            'locked_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user registered via admin.
     */
    public function registeredByAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_by' => 'admin',
            'registered_by_admin_id' => 1,
            'registration_notes' => fake()->sentence(),
        ]);
    }
}

