<?php

namespace App\Http\Controllers\Commons;

use App\Http\Controllers\Controller;
use App\Models\Commons\Message;
use App\Models\Commons\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function toggle(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|in:'.implode(',', Reaction::ALLOWED_EMOJI),
        ]);

        $message = Message::findOrFail($id);

        if ($message->isDeleted()) {
            return response()->json(['message' => 'Cannot react to a deleted message.'], 422);
        }

        $user = $request->user();
        $emoji = $request->input('emoji');

        $existing = Reaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Reaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
        }

        // Return updated reaction summary for this message
        $reactions = Reaction::where('message_id', $message->id)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->emoji => [
                'count' => $r->count,
                'reacted' => Reaction::where('message_id', $message->id)
                    ->where('user_id', $user->id)
                    ->where('emoji', $r->emoji)
                    ->exists(),
            ]]);

        return response()->json(['data' => $reactions]);
    }
}
