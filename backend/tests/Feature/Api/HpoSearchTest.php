<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('returns normalized HPO terms for a query', function () {
    Http::fake(['ontology.jax.org/*' => Http::response(['terms' => [
        ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => null, 'synonyms' => []],
    ]], 200)]);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/hpo/search?q=seizure');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', 'HP:0001250')
        ->assertJsonPath('data.0.label', 'Seizure');
});

it('requires a query parameter', function () {
    $this->actingAs($this->user, 'sanctum')->getJson('/api/hpo/search')->assertStatus(422);
});

it('requires authentication', function () {
    $this->getJson('/api/hpo/search?q=seizure')->assertStatus(401);
});
