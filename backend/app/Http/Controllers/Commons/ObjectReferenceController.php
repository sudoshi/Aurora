<?php

namespace App\Http\Controllers\Commons;

use App\Http\Controllers\Controller;
use App\Models\Commons\ObjectReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObjectReferenceController extends Controller
{
    /**
     * Search platform objects for the reference picker.
     * Returns matching objects across available types.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|string|max:50',
        ]);

        // Aurora's object reference search will be populated as clinical models are added
        $results = [];

        return response()->json(['data' => $results]);
    }

    /**
     * Get all messages that reference a specific object.
     */
    public function discussions(string $type, int $id): JsonResponse
    {
        $refs = ObjectReference::where('referenceable_type', $type)
            ->where('referenceable_id', $id)
            ->with(['message.user:id,name', 'message.channel:id,slug'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $messages = $refs->map(fn (ObjectReference $ref) => [
            'id' => $ref->message->id,
            'body' => $ref->message->body,
            'user' => $ref->message->user,
            'channel' => $ref->message->channel,
            'created_at' => $ref->message->created_at,
        ]);

        return response()->json(['data' => $messages]);
    }
}
