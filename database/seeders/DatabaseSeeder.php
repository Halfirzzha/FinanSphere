<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Create default admin user with complete information
        $admin = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'admin',
            'full_name' => 'Halfirzzha & Team IT FinanSphere',
            'email' => 'admin@finbrain.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'phone_number' => '+6281234567890',
            'birth_date' => '1990-01-01',
            'registered_by' => 'system',
            'registered_by_admin_id' => null,
            'registration_notes' => 'Default system administrator account',
            'password_changed_at' => now(),
            'password_changed_by' => 'system',
            'password_change_count' => 0,
            'is_active' => true,
            'is_locked' => false,
        ]);

        // Assign super_admin role to default admin
        $admin->assignRole('super_admin');
    }
}

