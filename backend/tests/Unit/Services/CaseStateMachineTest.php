<?php

use App\Models\CaseTemplate;
use App\Services\CaseStateMachine;

beforeEach(function () {
    $this->sm = new CaseStateMachine;
    $this->fsm = new CaseTemplate(['slug' => 'r', 'state_machine' => [
        'initial' => 'referral',
        'states' => ['referral', 'testing', 'mdt_review'],
        'transitions' => [
            ['from' => 'referral', 'to' => 'testing', 'event' => 'order'],
            ['from' => 'testing', 'to' => 'mdt_review', 'event' => 'results'],
        ],
    ]]);
    $this->stateless = new CaseTemplate(['slug' => 's', 'state_machine' => null]);
});

it('returns the initial state for a stateful template', function () {
    expect($this->sm->initialState($this->fsm))->toBe('referral');
});

it('returns null initial state for a stateless template', function () {
    expect($this->sm->initialState($this->stateless))->toBeNull();
});

it('allows a declared transition and rejects an undeclared one', function () {
    expect($this->sm->canTransition($this->fsm, 'referral', 'testing'))->toBeTrue();
    expect($this->sm->canTransition($this->fsm, 'referral', 'mdt_review'))->toBeFalse();
});

it('treats every transition as a no-op (allowed) for a stateless template', function () {
    expect($this->sm->canTransition($this->stateless, 'anything', 'whatever'))->toBeTrue();
});

it('rejects every transition for a stateful template with no declared transitions', function () {
    $terminal = new CaseTemplate(['slug' => 't', 'state_machine' => [
        'initial' => 'done',
        'states' => ['done'],
        'transitions' => [],
    ]]);
    expect($this->sm->canTransition($terminal, 'done', 'anything'))->toBeFalse();
});
