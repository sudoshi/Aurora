<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class OidcEmailAlias extends Model
{
    protected $table = 'app.oidc_email_aliases';

    protected $fillable = ['alias_email', 'canonical_email', 'note'];

    public static function canonicalFor(string $email): ?string
    {
        $row = static::query()
            ->whereRaw('lower(alias_email) = ?', [strtolower($email)])
            ->first();

        return $row?->canonical_email;
    }
}
