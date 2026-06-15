<?php

use App\Models\Auth\OidcEmailAlias;
use App\Models\Auth\UserExternalIdentity;
use App\Models\User;
use App\Services\Auth\Oidc\Exceptions\OidcAccessDeniedException;
use App\Services\Auth\Oidc\OidcReconciliationService;
use App\Services\Auth\Oidc\ValidatedClaims;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // DatabaseTruncation wipes the DB but not Spatie's cached registrar, and the
    // role pivot can survive identity-restart id reuse — reset both for isolation.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    DB::table(config('permission.table_names.model_has_roles'))->delete();

    foreach (['admin', 'super-admin', 'clinician', 'viewer'] as $name) {
        Role::findOrCreate($name, 'sanctum');
    }

    $this->svc = new OidcReconciliationService(['Aurora Admins']);
});

it('links by existing external subject without mutating roles or creating rows', function () {
    $user = User::factory()->create(['email' => 'admin@acumenus.net', 'is_active' => true]);
    $user->assignRole('super-admin');

    UserExternalIdentity::create([
        'user_id' => $user->id,
        'provider' => 'authentik',
        'provider_subject' => 'sub-1',
        'provider_email_at_link' => 'sudoshi@acumenus.net',
        'linked_at' => now(),
    ]);

    $usersBefore = User::count();
    $identitiesBefore = UserExternalIdentity::count();

    $result = $this->svc->reconcile(new ValidatedClaims('sub-1', 'sudoshi@acumenus.net', 'Sanjay Udoshi', ['Aurora Admins']));

    expect($result['reason'])->toBe('linked_by_sub')
        ->and($result['user']->id)->toBe($user->id)
        ->and(User::count())->toBe($usersBefore)
        ->and(UserExternalIdentity::count())->toBe($identitiesBefore)
        ->and($user->fresh()->getRoleNames()->sort()->values()->all())->toBe(['super-admin']);
});

it('links an existing user by exact email and preserves their roles', function () {
    $user = User::factory()->create(['email' => 'jdawe@acumenus.net', 'is_active' => true]);
    $user->assignRole('super-admin');
    $user->assignRole('clinician');

    $result = $this->svc->reconcile(new ValidatedClaims('sub-john', 'jdawe@acumenus.net', 'John Dawe', ['Aurora Admins']));

    expect($result['reason'])->toBe('linked_by_email')
        ->and($result['user']->id)->toBe($user->id)
        ->and($user->fresh()->getRoleNames()->sort()->values()->all())->toBe(['clinician', 'super-admin'])
        ->and(UserExternalIdentity::where('user_id', $user->id)->count())->toBe(1);
});

it('links by an approved email alias and never auto-adds admin to an existing user', function () {
    OidcEmailAlias::create([
        'alias_email' => 'sudoshi@authentik.work',
        'canonical_email' => 'admin@acumenus.net',
    ]);

    $user = User::factory()->create(['email' => 'admin@acumenus.net', 'is_active' => true]);
    $user->assignRole('super-admin');

    $result = $this->svc->reconcile(new ValidatedClaims(
        'sub-sanjay',
        'sudoshi@authentik.work',
        'Sanjay Udoshi',
        ['Aurora Admins', 'authentik Admins'],
    ));

    expect($result['reason'])->toBe('linked_by_alias')
        ->and($result['user']->id)->toBe($user->id)
        ->and($user->fresh()->hasRole('super-admin'))->toBeTrue()
        ->and($user->fresh()->hasRole('admin'))->toBeFalse();
});

it('case-insensitively links by alias', function () {
    OidcEmailAlias::create([
        'alias_email' => 'dmuraco@acumenus.net',
        'canonical_email' => 'david.muraco@gmail.com',
    ]);

    $user = User::factory()->create(['email' => 'david.muraco@gmail.com', 'is_active' => true]);
    $user->assignRole('super-admin');

    $result = $this->svc->reconcile(new ValidatedClaims('sub-david', 'DMURACO@ACUMENUS.NET', 'David Muraco', ['Aurora Admins']));

    expect($result['reason'])->toBe('linked_by_alias')
        ->and($result['user']->id)->toBe($user->id);
});

it('JIT-creates an active admin (never super-admin) for an allowed group', function () {
    $result = $this->svc->reconcile(new ValidatedClaims('sub-lisa', 'lmiller@acumenus.net', 'Lisa Miller', ['Aurora Admins']));

    expect($result['reason'])->toBe('created_jit')
        ->and($result['user']->email)->toBe('lmiller@acumenus.net')
        ->and($result['user']->getRoleNames()->sort()->values()->all())->toBe(['admin'])
        ->and($result['user']->must_change_password)->toBeFalse()
        ->and($result['user']->is_active)->toBeTrue()
        ->and($result['user']->email_verified_at)->not->toBeNull();
});

it('rejects JIT creation when the user is not in an allowed group', function () {
    $usersBefore = User::count();
    $identitiesBefore = UserExternalIdentity::count();

    expect(fn () => $this->svc->reconcile(new ValidatedClaims('sub-new', 'stranger@example.com', 'Stranger', ['authentik Admins'])))
        ->toThrow(OidcAccessDeniedException::class);

    expect(User::count())->toBe($usersBefore)
        ->and(UserExternalIdentity::count())->toBe($identitiesBefore);
});

it('is idempotent across repeated logins for the same subject', function () {
    $user = User::factory()->create(['email' => 'jdawe@acumenus.net', 'is_active' => true]);
    $user->assignRole('clinician');

    $claims = new ValidatedClaims('sub-dup', 'jdawe@acumenus.net', 'John Dawe', ['Aurora Admins']);

    $first = $this->svc->reconcile($claims);
    $second = $this->svc->reconcile($claims);

    expect($first['reason'])->toBe('linked_by_email')
        ->and($second['reason'])->toBe('linked_by_sub')
        ->and(UserExternalIdentity::where('user_id', $user->id)->count())->toBe(1);
});
