<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Super Admin role if not exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        // Create User role (default role for new registrations)
        // Check both 'user' and 'User' for case sensitivity
        $userRole = Role::where('name', 'user')->orWhere('name', 'User')->first();
        if (! $userRole) {
            $userRole = Role::create([
                'name' => 'user',
                'guard_name' => 'web',
            ]);
        }

        // Get all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to super_admin
        $superAdminRole->syncPermissions($allPermissions);

        // Define limited permissions for regular users
        $userPermissions = [
            // Category permissions - view only
            'view_category',
            'view_any_category',

            // Transaction permissions - full CRUD for own data
            'view_transaction',
            'view_any_transaction',
            'create_transaction',
            'update_transaction',
            'delete_transaction',

            // Debt permissions - full CRUD for own data
            'view_debt',
            'view_any_debt',
            'create_debt',
            'update_debt',
            'delete_debt',

            // Widget permissions - view dashboard widgets
            'widget_AnalysisCard',
            'widget_WidgetIncomeChart',
            'widget_WidgetExpenseChart',
            'widget_DebtTableWidget',

            // Page permissions - access filter page
            'page_FilterDate',
        ];

        // Assign limited permissions to user role
        $existingPermissions = Permission::whereIn('name', $userPermissions)->get();
        $userRole->syncPermissions($existingPermissions);

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info("📊 Super Admin: {$superAdminRole->permissions->count()} permissions");
        $this->command->info("📊 User: {$userRole->permissions->count()} permissions");
    }
}
