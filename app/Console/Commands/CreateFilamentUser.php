<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateFilamentUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:filament-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Filament admin user with complete information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Create a new Filament admin user');
        $this->newLine();

        // Get user information
        $fullName = $this->ask('Full Name');
        $username = $this->ask('Username');
        $email = $this->ask('Email address');
        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm Password');

        // Validate password confirmation
        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return self::FAILURE;
        }

        // Optional information
        $this->newLine();
        $this->info('Optional Information (press Enter to skip)');

        $phoneNumber = $this->ask('Phone Number (optional)');

        $addBirthDate = $this->confirm('Add birth date information?', false);
        $birthDate = null;

        if ($addBirthDate) {
            $birthDate = $this->ask('Birth Date (YYYY-MM-DD format, e.g., 1990-06-15)');
        }

        try {
            // Create user
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'username' => $username,
                'full_name' => $fullName,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'phone_number' => $phoneNumber ?: null,
                'birth_date' => $birthDate,
                'registered_by' => 'admin',
                'registered_by_admin_id' => null,
                'registration_notes' => 'Created via Artisan command',
                'password_changed_at' => now(),
                'password_changed_by' => 'system',
                'password_change_count' => 0,
                'is_active' => true,
                'is_locked' => false,
            ]);

            $this->newLine();
            $this->info('Success!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->id],
                    ['UUID', $user->uuid],
                    ['Username', $user->username],
                    ['Full Name', $user->full_name],
                    ['Email', $user->email],
                    ['Phone', $user->phone_number ?? '-'],
                    ['Birth Date', $user->birth_date ?? '-'],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
