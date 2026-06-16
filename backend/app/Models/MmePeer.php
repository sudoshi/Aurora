<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmePeer extends Model
{
    use HasFactory;

    protected $table = 'app.mme_peers';

    protected static function newFactory()
    {
        return \Database\Factories\MmePeerFactory::new();
    }

    protected $fillable = [
        'name',
        'base_url',
        'auth_token',
        'direction',
        'active',
        'contact_email',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'auth_token' => 'encrypted',
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    public function scopeOutbound($q)
    {
        return $q->whereIn('direction', ['outbound', 'both'])->whereNotNull('base_url');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MmeMatch::class, 'peer_id');
    }
}
