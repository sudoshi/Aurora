<?php

use App\Models\ClinicalCase;
use App\Models\Decision;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

$draft = [
    'decision_type' => 'treatment_recommendation', 'recommendation' => 'Start therapy X',
    'rationale' => 'Per PMID:100', 'confidence' => 0.82, 'guideline_references' => ['NCCN'],
    'sources' => [['type' => 'article', 'id' => 'PMID:100', 'title' => 't', 'url' => 'u']],
    'model' => 'claude-x', 'evidence_counts' => ['articles' => 1, 'trials' => 0, 'variants' => 0],
];

it('proxies a decision draft from the ai service', function () use ($draft) {
    Http::fake([config('services.ai.base_url').'/*' => Http::response($draft, 200)]);
    $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
    $r = $this->actingAs($this->user, 'sanctum')->postJson("/api/cases/{$case->id}/decisions/draft");
    $r->assertStatus(200)->assertJsonPath('data.recommendation', 'Start therapy X')->assertJsonPath('data.confidence', 0.82);
    expect($r->json('data.sources'))->toHaveCount(1);
});

it('returns 502 when the ai service errors', function () {
    Http::fake([config('services.ai.base_url').'/*' => Http::response(['detail' => 'x'], 502)]);
    $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum')->postJson("/api/cases/{$case->id}/decisions/draft")->assertStatus(502);
});

it('requires auth for the draft endpoint', function () {
    $case = ClinicalCase::factory()->create();
    $this->withHeaders(['Accept' => 'application/json'])->postJson("/api/cases/{$case->id}/decisions/draft")->assertStatus(401);
});

it('captures ai-attribution when a drafted decision is recorded', function () use ($draft) {
    $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
    $payload = array_merge($draft, ['ai_generated' => true]);
    unset($payload['evidence_counts'], $payload['guideline_references']); // not Decision columns
    $r = $this->actingAs($this->user, 'sanctum')->postJson("/api/cases/{$case->id}/decisions", $payload);
    $r->assertStatus(201);
    $d = Decision::where('case_id', $case->id)->where('ai_generated', true)->first();
    expect($d)->not->toBeNull();
    expect($d->ai_model)->toBe('claude-x');
    expect($d->ai_sources)->toHaveCount(1);
    expect($d->ai_drafted_at)->not->toBeNull();
});
