<?php

use App\Models\Commons\Channel;
use App\Models\Commons\ChannelMember;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast channel authorization
|--------------------------------------------------------------------------
|
| Direct tests for the /broadcasting/auth handshake (registered in
| bootstrap/app.php under the auth:sanctum guard). The HTTP-403-on-controller
| tests cover the REST surface; these exercise the channel callbacks in
| routes/channels.php so an unauthorized subscriber is denied at the
| websocket-auth boundary, not just at the API.
|
| The default test broadcaster is `null` (phpunit.xml), whose auth() is a
| no-op that authorizes everything -> useless for testing denial. We therefore
| force the `pusher` driver here, which evaluates the channel callbacks via
| Broadcaster::verifyUserCanAccessChannel(): a denied subscription throws
| AccessDeniedHttpException -> 403 (no network), and a permitted one returns
| 200 with a locally-computed HMAC auth payload (no network). We assert the
| denial cases as a firm 403 and the positive cases as "not 403" (200).
|
*/

beforeEach(function () {
    // Seeds the sanctum-guard roles so authorize() behaves as in the rest of
    // the suite (mirrors CommonsBroadcastTest / CaseControllerTest).
    app(\Database\Seeders\SuperuserSeeder::class)->run();

    // Swap off the no-op null broadcaster so the channel callbacks in
    // routes/channels.php actually run. Pusher auth resolves entirely
    // locally (HMAC over the app secret) for both 403 and 200 paths.
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app',
    ]);

    // Channel callbacks are bound to whatever connection was default at boot
    // (null, from phpunit.xml). After switching the default to pusher we must
    // re-run the registrations so the new connection knows the callbacks;
    // otherwise verifyUserCanAccessChannel() has no match and denies everyone.
    require base_path('routes/channels.php');
});

function broadcastActor(): User
{
    return User::factory()->create()->assignRole('super-admin');
}

it('denies a non-member subscribing to a private channel', function () {
    $channel = Channel::factory()->private()->create();
    $outsider = broadcastActor();

    $this->actingAs($outsider, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => "private-commons.channel.{$channel->id}",
        ])
        ->assertStatus(403);
});

it('allows a member subscribing to a private channel', function () {
    $channel = Channel::factory()->private()->create();
    $member = broadcastActor();
    ChannelMember::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $member->id,
        'role' => 'member',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => "private-commons.channel.{$channel->id}",
        ]);

    // Member passes the channel callback: auth succeeds (200 + HMAC payload).
    // The contract under test is "not denied".
    expect($response->getStatusCode())->not->toBe(403);
    $response->assertStatus(200);
});

it('denies subscribing to another user private notification channel', function () {
    $user = broadcastActor();
    $other = broadcastActor();

    $this->actingAs($user, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => "private-App.Models.User.{$other->id}",
        ])
        ->assertStatus(403);
});

it('allows subscribing to your own private notification channel', function () {
    $user = broadcastActor();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => "private-App.Models.User.{$user->id}",
        ]);

    expect($response->getStatusCode())->not->toBe(403);
    $response->assertStatus(200);
});

it('allows any authenticated user on a public channel', function () {
    $channel = Channel::factory()->create(); // visibility defaults to public
    $stranger = broadcastActor();

    $response = $this->actingAs($stranger, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => "private-commons.channel.{$channel->id}",
        ]);

    expect($response->getStatusCode())->not->toBe(403);
    $response->assertStatus(200);
});
