<?php

namespace App\Services\Auth\Oidc;

use App\Models\Auth\OidcEmailAlias;
use App\Models\Auth\UserExternalIdentity;
use App\Models\User;
use App\Services\Auth\Oidc\Exceptions\OidcAccessDeniedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OidcReconciliationService
{
    private const PROVIDER = 'authentik';

    /**
     * @param  list<string>  $allowedGroups
     */
    public function __construct(
        private readonly array $allowedGroups = ['Aurora Admins'],
    ) {}

    /**
     * @return array{user: User, reason: string}
     */
    public function reconcile(ValidatedClaims $claims): array
    {
        /** @var array{user: User, reason: string} $result */
        $result = DB::transaction(function () use ($claims): array {
            $identity = UserExternalIdentity::query()
                ->where('provider', self::PROVIDER)
                ->where('provider_subject', $claims->sub)
                ->first();

            if ($identity !== null) {
                $user = $identity->user;
                if ($user === null) {
                    throw new OidcAccessDeniedException('linked_user_missing', 'Linked Aurora user no longer exists.');
                }

                $this->assertUserActive($user);

                return ['user' => $user->load('roles.permissions'), 'reason' => 'linked_by_sub'];
            }

            $canonical = strtolower($claims->email);

            $user = User::query()->whereRaw('lower(email) = ?', [$canonical])->first();
            if ($user !== null) {
                $this->assertUserActive($user);
                $this->createIdentityLink($user->id, $claims);

                return ['user' => $user->load('roles.permissions'), 'reason' => 'linked_by_email'];
            }

            $aliased = OidcEmailAlias::canonicalFor($canonical);
            if ($aliased !== null) {
                $user = User::query()->whereRaw('lower(email) = ?', [$aliased])->first();
                if ($user !== null) {
                    $this->assertUserActive($user);
                    $this->createIdentityLink($user->id, $claims);

                    return ['user' => $user->load('roles.permissions'), 'reason' => 'linked_by_alias'];
                }
            }

            if (! $this->isGroupAllowed($claims->groups)) {
                throw new OidcAccessDeniedException(
                    'not_in_allowed_group',
                    'User is not in an allowed Aurora group.'
                );
            }

            $user = User::query()->create([
                'name' => $claims->name,
                'email' => $canonical,
                'password' => bcrypt(Str::random(64)),
                'must_change_password' => false,
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            Role::findOrCreate('admin', 'sanctum');
            $user->assignRole('admin');

            $this->createIdentityLink($user->id, $claims);

            return ['user' => $user->load('roles.permissions'), 'reason' => 'created_jit'];
        });

        return $result;
    }

    private function assertUserActive(User $user): void
    {
        if (! $user->is_active) {
            throw new OidcAccessDeniedException('account_disabled', 'Linked Aurora user is disabled.');
        }
    }

    private function createIdentityLink(int $userId, ValidatedClaims $claims): void
    {
        UserExternalIdentity::query()->create([
            'user_id' => $userId,
            'provider' => self::PROVIDER,
            'provider_subject' => $claims->sub,
            'provider_email_at_link' => $claims->email,
            'linked_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $tokenGroups
     */
    private function isGroupAllowed(array $tokenGroups): bool
    {
        foreach ($this->allowedGroups as $allowed) {
            if (in_array($allowed, $tokenGroups, true)) {
                return true;
            }
        }

        return false;
    }
}
