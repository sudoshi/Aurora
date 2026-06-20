<?php

namespace App\Events\Commons;

use App\Models\Commons\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a message (top-level or reply) is created in a channel.
 *
 * Frontend contract: useChannelSubscription listens for "MessageSent" on the
 * private channel `commons.channel.{channelId}` and reads `event.message`.
 * The class name IS the broadcast name (default namespace), matching the
 * frontend's `.listen("MessageSent")` (no leading dot).
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message) {}

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
        return ['message' => $this->message->toArray()];
    }
}
