<?php

namespace App\Services\Genomics\Acmg;

enum AcmgStrength: string
{
    case VeryStrong = 'very_strong';
    case Strong = 'strong';
    case Moderate = 'moderate';
    case Supporting = 'supporting';

    /** Tavtigian 2018 / ClinGen SVI 2020 point magnitude (unsigned). */
    public function points(): int
    {
        return match ($this) {
            self::VeryStrong => 8,
            self::Strong => 4,
            self::Moderate => 2,
            self::Supporting => 1,
        };
    }
}
