<?php

namespace App\Services;

use App\Models\CaseTemplate;

class CaseStateMachine
{
    public function initialState(CaseTemplate $template): ?string
    {
        return $template->state_machine['initial'] ?? null;
    }

    /**
     * Null state_machine ⇒ stateless template ⇒ no constraints (always true).
     */
    public function canTransition(CaseTemplate $template, string $from, string $to): bool
    {
        $fsm = $template->state_machine;
        if (empty($fsm)) {
            return true; // genuinely stateless template ⇒ no constraints
        }
        if (empty($fsm['transitions'])) {
            return false; // stateful but no transitions defined ⇒ terminal, nothing allowed
        }

        foreach ($fsm['transitions'] as $t) {
            if (($t['from'] ?? null) === $from && ($t['to'] ?? null) === $to) {
                return true;
            }
        }

        return false;
    }
}
