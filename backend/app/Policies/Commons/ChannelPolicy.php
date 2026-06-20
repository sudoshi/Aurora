<?php

namespace App\Policies\Commons;

use App\Models\Commons\Channel;
use App\Models\Commons\ChannelMember;
use App\Models\User;

/**
 * Authorization for Commons channels.
 *
 * Previously absent — which made `$this->authorize('view', $channel)` in the
 * commons controllers deny every user (no policy + no gate => 403), silently
 * breaking message posting. `view` mirrors the realtime channel auth in
 * routes/channels.php (member-or-public); `update` is owner/admin only.
 */
class ChannelPolicy
{
    public function view(User $user, Channel $channel): bool
    {
        if ($channel->visibility === 'public') {
            return true;
        }

        return ChannelMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, Channel $channel): bool
    {
        return ChannelMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }
}
