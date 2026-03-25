<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'fingerprint.search',
            'fingerprint.view',
            'fingerprint.encode',
            'fingerprint.assess',
            'fingerprint.admin',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }

        // Grant all permissions to admin role
        $admin = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Grant search/view/encode/assess to clinical roles (attending, fellow, resident)
        foreach (['attending', 'fellow', 'resident'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
            if ($role) {
                $role->givePermissionTo([
                    'fingerprint.search',
                    'fingerprint.view',
                    'fingerprint.encode',
                    'fingerprint.assess',
                ]);
            }
        }

        // Grant search/view to support clinical roles
        foreach (['department_head', 'nurse_coordinator', 'data_analyst'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
            if ($role) {
                $role->givePermissionTo(['fingerprint.search', 'fingerprint.view']);
            }
        }
    }

    public function down(): void
    {
        $permissions = ['fingerprint.search', 'fingerprint.view', 'fingerprint.encode', 'fingerprint.assess', 'fingerprint.admin'];
        foreach ($permissions as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
