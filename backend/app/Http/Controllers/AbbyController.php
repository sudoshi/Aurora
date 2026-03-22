<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\AbbyConversation;
use App\Models\AbbyMessage;
use Illuminate\Http\Request;

class AbbyController extends Controller
{
    /**
     * List conversations for the authenticated user.
     */
    public function conversations(Request $request)
    {
        $conversations = AbbyConversation::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->paginate($request->get('per_page', 20));

        return ApiResponse::success($conversations);
    }

    /**
     * Show a single conversation with its messages.
     */
    public function showConversation(Request $request, int $id)
    {
        $conversation = AbbyConversation::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->firstOrFail();

        return ApiResponse::success($conversation);
    }

    /**
     * Create a new conversation.
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'page_context' => 'nullable|string|max:100',
        ]);

        $conversation = AbbyConversation::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title', 'New Conversation'),
            'page_context' => $request->input('page_context', 'general'),
        ]);

        return ApiResponse::success($conversation, 'Conversation created', 201);
    }

    /**
     * Delete a conversation and its messages.
     */
    public function deleteConversation(Request $request, int $id)
    {
        $conversation = AbbyConversation::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $conversation->messages()->delete();
        $conversation->delete();

        return ApiResponse::success(null, 'Conversation deleted');
    }

    /**
     * Non-streaming chat endpoint — proxies to AI service or handles locally.
     * This is the fallback when SSE streaming is not available.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'page_context' => 'nullable|string',
            'conversation_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $conversationId = $request->input('conversation_id');

        // Create or find conversation
        if ($conversationId) {
            $conversation = AbbyConversation::where('user_id', $user->id)
                ->where('id', $conversationId)
                ->firstOrFail();
        } else {
            $conversation = AbbyConversation::create([
                'user_id' => $user->id,
                'title' => $request->input('title', substr($request->input('message'), 0, 50)),
                'page_context' => $request->input('page_context', 'general'),
            ]);
        }

        // Store user message
        AbbyMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->input('message'),
        ]);

        // Try to call AI service
        $reply = 'I received your message. The AI service is being configured — full responses will be available soon.';
        $suggestions = [
            'Tell me about a patient case',
            'Help me prepare for a tumor board',
            'What clinical data is available?',
        ];

        try {
            $aiResponse = \Illuminate\Support\Facades\Http::timeout(30)
                ->post(config('services.ai.base_url', 'http://localhost:8100') . '/api/ai/abby/chat', [
                    'message' => $request->input('message'),
                    'page_context' => $request->input('page_context', 'general'),
                    'history' => $request->input('history', []),
                    'user_profile' => [
                        'name' => $user->name,
                        'roles' => $user->roles?->pluck('name')->toArray() ?? [],
                    ],
                    'conversation_id' => $conversation->id,
                ]);

            if ($aiResponse->successful()) {
                $aiData = $aiResponse->json();
                $reply = $aiData['reply'] ?? $reply;
                $suggestions = $aiData['suggestions'] ?? $suggestions;
            }
        } catch (\Exception $e) {
            // AI service unavailable — use fallback reply
        }

        // Store assistant message
        AbbyMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $conversation->touch();

        return response()->json([
            'reply' => $reply,
            'suggestions' => $suggestions,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Generate a title for a conversation from its messages.
     */
    public function generateTitle(Request $request, int $id)
    {
        $conversation = AbbyConversation::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $firstMessage = $conversation->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            $conversation->update([
                'title' => substr($firstMessage->content, 0, 80),
            ]);
        }

        return ApiResponse::success($conversation);
    }
}
