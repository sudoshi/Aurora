<?php

use App\Services\RareDisease\OdysseyStateMachine;

beforeEach(function () {
    $this->machine = new OdysseyStateMachine;
});

it('allows referral to phenotyping', function () {
    expect($this->machine->canTransition('referral', 'phenotyping'))->toBeTrue();
});

it('rejects referral straight to diagnosed', function () {
    expect($this->machine->canTransition('referral', 'diagnosed'))->toBeFalse();
});

it('allows mdt_review to reanalysis', function () {
    expect($this->machine->canTransition('mdt_review', 'reanalysis'))->toBeTrue();
});

it('treats closed as terminal', function () {
    expect($this->machine->allowedFrom('closed'))->toBe([]);
});

it('derives solved progress status for diagnosed', function () {
    expect($this->machine->progressStatusFor('diagnosed'))->toBe('solved');
});

it('derives unsolved progress status for reanalysis', function () {
    expect($this->machine->progressStatusFor('reanalysis'))->toBe('unsolved');
});

it('derives in_progress for intermediate states', function () {
    expect($this->machine->progressStatusFor('testing'))->toBe('in_progress');
});
