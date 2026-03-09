<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperuserSeeder extends Seeder
{
    /**
     * The roles to seed for Aurora V2.
     */
    private const ROLES = [
        'admin',
        'department_head',
        'attending',
        'fellow',
        'resident',
        'nurse_coordinator',
        'data_analyst',
        'observer',
    ];

    /**
     * Seed roles and the superuser account.
     */
    public function run(): void
    {
        // Create all roles (idempotent via firstOrCreate)
        foreach (self::ROLES as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'sanctum'],
            );
        }

        // Create or update the superuser
        $superuser = User::updateOrCreate(
            ['email' => 'admin@acumenus.net'],
            [
                'name' => 'Aurora Admin',
                'password' => Hash::make('superuser'),
                'must_change_password' => false,
                'is_active' => true,
            ],
        );

        // Assign all roles to superuser
        $superuser->syncRoles(self::ROLES);
    }
}
