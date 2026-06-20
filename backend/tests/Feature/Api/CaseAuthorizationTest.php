<?php

use App\Models\CaseTeamMember;
use App\Models\ClinicalCase;
use App\Models\User;

it('lets the creator view their case but forbids an outsider', function () {
    $creator = User::factory()->create();
    $case = ClinicalCase::factory()->create(['created_by' => $creator->id]);
    $outsider = User::factory()->create();

    $this->actingAs($creator, 'sanctum')->getJson("/api/cases/{$case->id}")->assertOk();
    $this->actingAs($outsider, 'sanctum')->getJson("/api/cases/{$case->id}")->assertStatus(403);
});

it('lets a team member view and update, but forbids outsiders from updating', function () {
    $creator = User::factory()->create();
    $case = ClinicalCase::factory()->create(['created_by' => $creator->id]);
    $member = User::factory()->create();
    CaseTeamMember::create(['case_id' => $case->id, 'user_id' => $member->id, 'role' => 'reviewer', 'invited_at' => now()]);
    $outsider = User::factory()->create();

    $this->actingAs($member, 'sanctum')->getJson("/api/cases/{$case->id}")->assertOk();
    $this->actingAs($member, 'sanctum')
        ->putJson("/api/cases/{$case->id}", ['title' => 'Updated by member'])
        ->assertOk();
    $this->actingAs($outsider, 'sanctum')
        ->putJson("/api/cases/{$case->id}", ['title' => 'Nope'])
        ->assertStatus(403);
});

it('reserves archiving a case to its creator', function () {
    $creator = User::factory()->create();
    $case = ClinicalCase::factory()->create(['created_by' => $creator->id]);
    $member = User::factory()->create();
    CaseTeamMember::create(['case_id' => $case->id, 'user_id' => $member->id, 'role' => 'reviewer', 'invited_at' => now()]);

    $this->actingAs($member, 'sanctum')->deleteJson("/api/cases/{$case->id}")->assertStatus(403);
    $this->actingAs($creator, 'sanctum')->deleteJson("/api/cases/{$case->id}")->assertOk();
});

it('forbids an outsider from managing the case team', function () {
    $creator = User::factory()->create();
    $case = ClinicalCase::factory()->create(['created_by' => $creator->id]);
    $outsider = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/cases/{$case->id}/team", ['user_id' => $target->id, 'role' => 'reviewer'])
        ->assertStatus(403);
});
