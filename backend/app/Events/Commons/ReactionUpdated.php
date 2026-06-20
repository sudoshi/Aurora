<?php

namespace App\Events\Commons;

use App\Models\Commons\Message;
use App\Models\Commons\Reaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a reaction is added or removed from a message.
 *
 * Frontend contract: useChannelSubscription listens for "ReactionUpdated" with
 * `{ message_id, emoji, user: {id,name}, action, summary }`, where summary is
 * Record<emoji, {count, users: [{id,name}]}>. The client derives the local
 * `reacted` flag from the users list, so the broadcast carries users (the HTTP
 * response to the actor keeps the lighter {count, reacted} shape).
 */
class ReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array{id: int, name: string}  $user
     */
    public function __construct(
        public Message $message,
        public string $emoji,
        public array $user,
        public string $action,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("commons.channel.{$this->message->channel_id}")];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $summary = Reaction::where('message_id', $this->message->id)
            ->with('user:id,name')
            ->get()
            ->groupBy('emoji')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'users' => $group->map(fn (Reaction $r) => [
                    'id' => $r->user_id,
                    'name' => $r->user?->name,
                ])->values(),
            ]);

        return [
            'message_id' => $this->message->id,
            'emoji' => $this->emoji,
            'user' => $this->user,
            'action' => $this->action,
            'summary' => $summary,
        ];
    }
}
