<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    DB::table(config('permission.table_names.model_has_roles'))->delete();
});

function makeAuroraSuperAdmin(string $email): User
{
    Role::findOrCreate('super-admin', 'sanctum');
    Role::findOrCreate('admin', 'sanctum');

    $user = User::query()->create([
        'name' => 'Super '.$email,
        'email' => $email,
        'password' => Hash::make('password'),
        'must_change_password' => false,
        'is_active' => true,
    ]);
    $user->syncRoles(['super-admin', 'admin']);

    return $user;
}

describe('Last super-admin protection', function () {
    it('refuses to delete the only super-admin', function () {
        $super = makeAuroraSuperAdmin('only-super@acumenus.net');

        $this->actingAs($super, 'sanctum')
            ->deleteJson("/api/admin/users/{$super->id}")
            ->assertStatus(422);

        expect(User::role('super-admin')->count())->toBe(1);
    });

    it('refuses to strip super-admin from the only super-admin', function () {
        $super = makeAuroraSuperAdmin('only-super-2@acumenus.net');

        $this->actingAs($super, 'sanctum')
            ->putJson("/api/admin/users/{$super->id}/roles", ['roles' => ['admin']])
            ->assertStatus(422);

        expect($super->fresh()->hasRole('super-admin'))->toBeTrue();
    });

    it('allows deleting a super-admin while another remains', function () {
        $superA = makeAuroraSuperAdmin('super-a@acumenus.net');
        $superB = makeAuroraSuperAdmin('super-b@acumenus.net');

        $this->actingAs($superA, 'sanctum')
            ->deleteJson("/api/admin/users/{$superB->id}")
            ->assertSuccessful();

        expect(User::role('super-admin')->count())->toBe(1);
    });
});
