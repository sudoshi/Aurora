<?php

namespace App\Services\RareDisease;

use RuntimeException;

class InvalidOdysseyTransitionException extends RuntimeException
{
    public function __construct(public string $from, public string $to)
    {
        parent::__construct("Illegal odyssey transition: {$from} → {$to}");
    }
}
