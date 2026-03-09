<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Aurora RBAC roles.
     *
     * Seed the five core roles and migrate any existing users whose
     * `role` column maps to one of these Spatie roles. Users with
     * the legacy role value 'admin' (or the superuser email
     * admin@acumenus.net) are assigned the 'admin' Spatie role.
     */
    public function up(): void
    {
        $guard = 'web';

        // Create core roles
        $roles = ['admin', 'attending', 'resident', 'nurse', 'viewer'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard],
            );
        }

        // Map existing users from the legacy `role` column to Spatie roles.
        // The users table has a `role` varchar column (default 'user').
        $roleMapping = [
            'admin'     => 'admin',
            'attending' => 'attending',
            'resident'  => 'resident',
            'nurse'     => 'nurse',
            'viewer'    => 'viewer',
            'user'      => 'viewer', // default legacy role maps to viewer
        ];

        $users = User::all();

        foreach ($users as $user) {
            $legacyRole = $user->role ?? 'user';
            $spatieRole = $roleMapping[$legacyRole] ?? 'viewer';

            if (!$user->hasRole($spatieRole)) {
                $user->assignRole($spatieRole);
            }
        }

        // Ensure admin@acumenus.net always has the admin role
        $superuser = User::where('email', 'admin@acumenus.net')->first();
        if ($superuser && !$superuser->hasRole('admin')) {
            $superuser->assignRole('admin');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all role assignments but leave legacy `role` column intact
        $tableNames = config('permission.table_names');

        if (!empty($tableNames)) {
            \Illuminate\Support\Facades\DB::table($tableNames['model_has_roles'])->truncate();
            \Illuminate\Support\Facades\DB::table($tableNames['roles'])
                ->whereIn('name', ['admin', 'attending', 'resident', 'nurse', 'viewer'])
                ->delete();
        }
    }
};
