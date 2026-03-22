<?php

namespace App\Http\Controllers\Commons;

use App\Http\Controllers\Controller;
use App\Models\Commons\Channel;
use App\Models\Commons\ChannelMember;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $channels = Channel::whereNull('archived_at')
            ->where('type', '!=', 'dm')
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
            })
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $channels]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:commons_channels,slug',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|in:topic,dm',
            'visibility' => 'sometimes|string|in:public,private',
        ]);

        $channel = Channel::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        // Creator becomes owner
        ChannelMember::create([
            'channel_id' => $channel->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $channel->loadCount('members');

        return response()->json(['data' => $channel], 201);
    }

    public function show(string $slug): JsonResponse
    {
        $channel = Channel::where('slug', $slug)
            ->withCount('members')
            ->firstOrFail();

        $this->authorize('view', $channel);

        return response()->json(['data' => $channel]);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $this->authorize('update', $channel);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|string|in:public,private',
        ]);

        $channel->update($validated);

        return response()->json(['data' => $channel]);
    }

    public function archive(Request $request, string $slug): JsonResponse
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $this->authorize('update', $channel);

        $channel->update(['archived_at' => now()]);

        return response()->json(['data' => $channel]);
    }
}
