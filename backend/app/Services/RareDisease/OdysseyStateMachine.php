<?php

namespace App\Services\RareDisease;

class OdysseyStateMachine
{
    public const STATES = [
        'referral', 'phenotyping', 'testing', 'prioritization',
        'mdt_review', 'matchmaking', 'diagnosed', 'reanalysis', 'closed',
    ];

    private const TRANSITIONS = [
        'referral' => ['phenotyping'],
        'phenotyping' => ['testing', 'mdt_review'],
        'testing' => ['prioritization'],
        'prioritization' => ['mdt_review'],
        'mdt_review' => ['matchmaking', 'diagnosed', 'reanalysis', 'testing'],
        'matchmaking' => ['mdt_review', 'diagnosed', 'reanalysis'],
        'reanalysis' => ['mdt_review', 'diagnosed'],
        'diagnosed' => ['closed', 'reanalysis'],
        'closed' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** @return string[] */
    public function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function progressStatusFor(string $to): string
    {
        return match ($to) {
            'diagnosed' => 'solved',
            'reanalysis' => 'unsolved',
            default => 'in_progress',
        };
    }
}
