<?php

namespace App\Events\Commons;

use App\Models\Commons\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast a new in-app notification to its recipient.
 *
 * Frontend contract: useNotificationListener subscribes to the private channel
 * `App.Models.User.{userId}` and listens for ".NotificationSent" (leading dot —
 * literal event name), reading `event.notification`. Hence broadcastAs() returns
 * the bare "NotificationSent".
 */
class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Notification $notification) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->notification->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'NotificationSent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->notification->loadMissing(['actor:id,name', 'channel:id,slug,name']);

        return ['notification' => $this->notification->toArray()];
    }
}
