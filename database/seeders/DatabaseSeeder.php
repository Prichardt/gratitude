<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create initial permissions
        $permissions = [
            'user:create',
            'user:update',
            'user:delete',
            'role:create',
            'role:update',
            'role:delete',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Super Admin role and assign all permissions
        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(\Spatie\Permission\Models\Permission::all());

        $user = User::firstOrCreate(
            ['email' => 'it@artinvoyage.com'],
            [
                'first_name' => 'IT',
                'last_name' => 'AIv',
                'name' => 'IT AIv',
                'password' => \Illuminate\Support\Facades\Hash::make('qwertyuiop'),
                'status' => 'active',
            ]
        );

        $user->assignRole($superAdminRole);
    }
}
