<?php

namespace App\Events\Commons;

use App\Models\Commons\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a message is edited or soft-deleted.
 *
 * Frontend contract: useChannelSubscription listens for "MessageUpdated" with
 * `{ message, action }`. `action` is "updated" or "deleted"; the client merges
 * the partial message into its cache by id.
 */
class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message,
        public string $action = 'updated',
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
        return [
            'message' => $this->message->toArray(),
            'action' => $this->action,
        ];
    }
}
