<?php

use App\Models\CaseTemplate;
use App\Services\BoardTemplateService;

beforeEach(function () {
    $this->svc = new BoardTemplateService;
    $this->tpl = new CaseTemplate(['slug' => 'x', 'data_schema' => [
        ['key' => 'primary_site', 'label' => 'Primary site', 'type' => 'string', 'required' => true],
        ['key' => 'stage', 'label' => 'Stage', 'type' => 'string', 'required' => false],
    ]]);
});

it('returns no warnings when required fields are present and typed', function () {
    $warnings = $this->svc->validate($this->tpl, ['primary_site' => 'lung', 'stage' => 'IV']);
    expect($warnings)->toBe([]);
});

it('warns (but does not throw) on a missing required field', function () {
    $warnings = $this->svc->validate($this->tpl, ['stage' => 'IV']);
    expect($warnings)->toHaveCount(1);
    expect($warnings[0])->toContain('primary_site');
});

it('warns on a wrong-typed field', function () {
    $warnings = $this->svc->validate($this->tpl, ['primary_site' => 123]);
    expect(collect($warnings)->implode(' '))->toContain('primary_site');
});

it('treats a null/empty data_schema as anything-goes', function () {
    $tpl = new CaseTemplate(['slug' => 'y', 'data_schema' => []]);
    expect($this->svc->validate($tpl, ['whatever' => 1]))->toBe([]);
});
