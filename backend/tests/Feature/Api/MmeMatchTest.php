<?php

use App\Models\DiagnosticOdyssey;
use App\Models\MmeMatch;
use App\Models\MmePeer;
use App\Models\PhenotypeFeature;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    MmeMatch::query()->delete();
    MmePeer::where('name', 'MME-TEST')->delete();
});

it('answers an inbound match with ranked local results', function () {
    MmePeer::factory()->create(['name' => 'MME-TEST', 'auth_token' => 'secret-token-123', 'active' => true]);
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250', 'excluded' => false]);

    $response = $this->withHeaders(['X-Auth-Token' => 'secret-token-123'])
        ->postJson('/api/mme/v1/match', ['patient' => ['id' => 'ext-1', 'contact' => ['name' => 'Dr X', 'href' => 'mailto:x@y'], 'features' => [['id' => 'HP:0001250', 'observed' => 'yes']]]]);

    $response->assertStatus(200)->assertJsonStructure(['results' => [['score' => ['patient'], 'patient' => ['id']]]]);
});

it('rejects an inbound match without a valid token', function () {
    $this->postJson('/api/mme/v1/match', ['patient' => ['id' => 'x', 'contact' => ['name' => 'a', 'href' => 'b'], 'features' => [['id' => 'HP:0001250']]]])
        ->assertStatus(401);
});

it('runs an outbound search and stores peer matches', function () {
    $user = User::where('email', 'admin@acumenus.net')->first();
    MmePeer::factory()->create(['name' => 'MME-TEST', 'base_url' => 'https://peer.test/mme', 'direction' => 'both', 'active' => true]);
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250']);
    Http::fake(['peer.test/*' => Http::response(['results' => [['score' => ['patient' => 0.9], 'patient' => ['id' => 'peer-1', 'label' => 'Case 1', 'contact' => ['name' => 'Dr Y', 'href' => 'mailto:y@z']]]]], 200)]);

    $this->actingAs($user, 'sanctum')->postJson("/api/odysseys/{$od->id}/mme-search")->assertStatus(200);

    expect(MmeMatch::where('odyssey_id', $od->id)->where('direction', 'outbound')->count())->toBe(1);
});
