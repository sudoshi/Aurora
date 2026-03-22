<?php

namespace App\Http\Controllers\Commons;

use App\Http\Controllers\Controller;
use App\Models\Commons\Channel;
use App\Models\Commons\Message;
use App\Models\Commons\ObjectReference;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, string $slug): JsonResponse
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $channel);

        $query = Message::where('channel_id', $channel->id)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->with(['user:id,name', 'objectReferences', 'attachments'])
            ->withCount('replies')
            ->withMax('replies', 'created_at')
            ->orderByDesc('id');

        if ($request->has('before')) {
            $query->where('id', '<', (int) $request->input('before'));
        }

        $limit = min((int) $request->input('limit', 50), 100);
        $messages = $query->limit($limit)->get();

        // Rename the withMax column for cleaner JSON
        $messages->each(function ($msg) {
            $msg->setAttribute('latest_reply_at', $msg->getAttribute('replies_max_created_at'));
            unset($msg->replies_max_created_at);
        });

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $channel);

        $validated = $request->validate([
            'body' => 'required|string|max:10000',
            'parent_id' => 'nullable|integer|exists:commons_messages,id',
            'references' => 'nullable|array',
            'references.*.type' => 'required_with:references|string',
            'references.*.id' => 'required_with:references|integer',
            'references.*.name' => 'required_with:references|string',
        ]);

        $depth = 0;
        if (! empty($validated['parent_id'])) {
            $parent = Message::findOrFail($validated['parent_id']);
            $depth = min($parent->depth + 1, 2);
        }

        $message = Message::create([
            'channel_id' => $channel->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'depth' => $depth,
            'body' => $validated['body'],
        ]);

        // Save object references if provided
        $refs = $request->input('references', []);
        if (is_array($refs)) {
            foreach ($refs as $ref) {
                if (isset($ref['type'], $ref['id'], $ref['name'])) {
                    ObjectReference::create([
                        'message_id' => $message->id,
                        'referenceable_type' => $ref['type'],
                        'referenceable_id' => (int) $ref['id'],
                        'display_name' => $ref['name'],
                    ]);
                }
            }
            $message->load('objectReferences');
        }

        $message->load('user:id,name');

        return response()->json(['data' => $message], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->user_id !== $request->user()->id) {
            abort(403, 'You can only edit your own messages.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:10000',
        ]);

        $message->update([
            'body' => $validated['body'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json(['data' => $message]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->user_id !== $request->user()->id) {
            abort(403, 'You can only delete your own messages.');
        }

        $message->update(['deleted_at' => now()]);

        return response()->json(['data' => $message]);
    }

    public function replies(Request $request, string $slug, int $messageId): JsonResponse
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $channel);

        $parent = Message::where('id', $messageId)
            ->where('channel_id', $channel->id)
            ->firstOrFail();

        // Fetch depth-1 children and depth-2 grandchildren (max depth = 2)
        $childIds = Message::where('parent_id', $parent->id)
            ->pluck('id');

        $replies = Message::where('channel_id', $channel->id)
            ->where(function ($q) use ($parent, $childIds) {
                $q->where('parent_id', $parent->id)
                    ->orWhereIn('parent_id', $childIds);
            })
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $replies]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:200',
            'channel' => 'nullable|string',
        ]);

        $query = Message::whereNull('deleted_at')
            ->whereNull('parent_id')
            ->whereRaw("to_tsvector('english', body) @@ plainto_tsquery('english', ?)", [$request->input('q')])
            ->with(['user:id,name', 'channel:id,slug,name'])
            ->orderByDesc('created_at')
            ->limit(50);

        if ($request->filled('channel')) {
            $channel = Channel::where('slug', $request->input('channel'))->first();
            if ($channel) {
                $query->where('channel_id', $channel->id);
            }
        }

        $messages = $query->get();

        return response()->json(['data' => $messages]);
    }
}
