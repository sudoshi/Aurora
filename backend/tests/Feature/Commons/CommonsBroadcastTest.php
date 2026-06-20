<?php

use App\Events\Commons\MessageSent;
use App\Events\Commons\MessageUpdated;
use App\Events\Commons\NotificationSent;
use App\Events\Commons\ReactionUpdated;
use App\Models\Commons\Channel;
use App\Models\Commons\ChannelMember;
use App\Models\Commons\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Seeds the sanctum-guard roles; authorize('view', $channel) clears for
    // role-holding users the same way it does in the rest of the suite.
    app(\Database\Seeders\SuperuserSeeder::class)->run();
});

function commonsActor(): User
{
    return User::factory()->create()->assignRole('super-admin');
}

function commonsChannelWithMember(User $user, string $role = 'member'): Channel
{
    $channel = Channel::factory()->create();
    ChannelMember::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
        'role' => $role,
    ]);

    return $channel;
}

it('broadcasts MessageSent on the channel when a message is posted', function () {
    Event::fake([MessageSent::class]);
    $user = commonsActor();
    $channel = commonsChannelWithMember($user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/commons/channels/{$channel->slug}/messages", ['body' => 'hello team'])
        ->assertStatus(201);

    Event::assertDispatched(MessageSent::class, function (MessageSent $e) use ($channel) {
        return $e->message->channel_id === $channel->id
            && $e->broadcastOn()[0]->name === "private-commons.channel.{$channel->id}"
            && array_key_exists('message', $e->broadcastWith());
    });
});

it('broadcasts MessageUpdated with action=updated on edit and action=deleted on delete', function () {
    Event::fake([MessageUpdated::class]);
    $user = commonsActor();
    $channel = commonsChannelWithMember($user);
    $message = Message::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/commons/messages/{$message->id}", ['body' => 'edited'])
        ->assertStatus(200);
    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/commons/messages/{$message->id}")
        ->assertStatus(200);

    Event::assertDispatched(MessageUpdated::class, fn (MessageUpdated $e) => $e->action === 'updated');
    Event::assertDispatched(MessageUpdated::class, fn (MessageUpdated $e) => $e->action === 'deleted');
});

it('broadcasts ReactionUpdated when a reaction is toggled', function () {
    Event::fake([ReactionUpdated::class]);
    $user = commonsActor();
    $channel = commonsChannelWithMember($user);
    $message = Message::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/commons/messages/{$message->id}/reactions", ['emoji' => 'thumbsup'])
        ->assertStatus(200);

    Event::assertDispatched(ReactionUpdated::class, function (ReactionUpdated $e) use ($channel) {
        return $e->emoji === 'thumbsup'
            && $e->action === 'added'
            && $e->broadcastOn()[0]->name === "private-commons.channel.{$channel->id}";
    });
});

it('creates and broadcasts a thread-reply notification to the parent author', function () {
    Event::fake([NotificationSent::class]);
    $author = commonsActor();
    $replier = commonsActor();
    $channel = commonsChannelWithMember($author);
    ChannelMember::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $replier->id,
    ]);
    $root = Message::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($replier, 'sanctum')
        ->postJson("/api/commons/channels/{$channel->slug}/messages", [
            'body' => 'replying',
            'parent_id' => $root->id,
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('commons_notifications', [
        'user_id' => $author->id,
        'type' => 'thread_reply',
        'actor_id' => $replier->id,
        'channel_id' => $channel->id,
    ]);

    Event::assertDispatched(NotificationSent::class, function (NotificationSent $e) use ($author) {
        return $e->notification->user_id === $author->id
            && $e->broadcastOn()[0]->name === "private-App.Models.User.{$author->id}"
            && $e->broadcastAs() === 'NotificationSent';
    });
});

it('does not notify when replying to your own message', function () {
    Event::fake([NotificationSent::class]);
    $user = commonsActor();
    $channel = commonsChannelWithMember($user);
    $root = Message::factory()->create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/commons/channels/{$channel->slug}/messages", [
            'body' => 'self reply',
            'parent_id' => $root->id,
        ])
        ->assertStatus(201);

    Event::assertNotDispatched(NotificationSent::class);
});
