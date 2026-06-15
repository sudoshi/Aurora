<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\User;
use App\Services\RareDisease\InvalidOdysseyTransitionException;
use App\Services\RareDisease\OdysseyService;
use App\Services\RareDisease\OdysseyStateMachine;
use Illuminate\Foundation\Testing\DatabaseTruncation;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->service = new OdysseyService(new OdysseyStateMachine);
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
});

it('creates an odyssey in referral with an initial transition row', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    expect($odyssey->status)->toBe('referral');
    expect($odyssey->progress_status)->toBe('in_progress');
    expect($odyssey->transitions()->count())->toBe(1);
    expect($odyssey->transitions()->first()->to_status)->toBe('referral');
});

it('transitions through allowed states and records audit rows', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    $odyssey = $this->service->transition($odyssey, 'phenotyping', $this->user->id, 'Started phenotyping');

    expect($odyssey->status)->toBe('phenotyping');
    expect($odyssey->transitions()->count())->toBe(2);
});

it('sets solved progress and solved_at when diagnosed', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'phenotyping', $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'mdt_review', $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'diagnosed', $this->user->id);

    expect($odyssey->status)->toBe('diagnosed');
    expect($odyssey->progress_status)->toBe('solved');
    expect($odyssey->solved_at)->not->toBeNull();
});

it('throws on an illegal transition', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    $this->service->transition($odyssey, 'diagnosed', $this->user->id);
})->throws(InvalidOdysseyTransitionException::class);
