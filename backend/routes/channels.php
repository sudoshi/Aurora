<?php

use App\Models\Commons\Channel;
use App\Models\Commons\ChannelMember;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Authorization callbacks for the realtime channels the Commons frontend
| subscribes to. Registered with the `auth:sanctum` guard via
| bootstrap/app.php so the SPA's bearer token authorizes the handshake.
|
*/

// A user may only receive their own private notification channel.
Broadcast::channel('App.Models.User.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

// A channel's realtime stream is open to members, or to anyone if the channel
// is public — mirrors the ChannelPolicy 'view' check used by the REST API.
Broadcast::channel('commons.channel.{channelId}', function (User $user, int $channelId) {
    $channel = Channel::find($channelId);

    if (! $channel) {
        return false;
    }

    if ($channel->visibility === 'public') {
        return true;
    }

    return ChannelMember::where('channel_id', $channelId)
        ->where('user_id', $user->id)
        ->exists();
});

// Global presence: every authenticated user is allowed; the returned payload is
// what populates usePresence()'s PresenceUser list.
Broadcast::channel('commons.online', function (User $user) {
    return ['id' => $user->id, 'name' => $user->name];
});
