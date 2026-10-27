<?php

declare(strict_types=1);

namespace DbChatbot\Http\Controllers;

use DbChatbot\Chat\ChatContext;
use DbChatbot\Chat\Drivers\ChatDriver;
use DbChatbot\Chat\Suggestions;
use DbChatbot\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $suggestions = Suggestions::for($user);

        $messages = ChatMessage::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
                'type' => $message->meta['type'] ?? 'text',
                'rows' => $message->meta['rows'] ?? [],
                'columns' => $message->meta['columns'] ?? [],
                'fileUrl' => $message->meta['fileUrl'] ?? null,
                'quickReplies' => $message->meta['quickReplies'] ?? [],
            ])
            ->all();

        $quickReplies = $suggestions;
        $last = collect($messages)->last();

        if (is_array($last) && $last['role'] === 'assistant' && $last['quickReplies'] !== []) {
            $quickReplies = $last['quickReplies'];
        }

        return view('db-chatbot::chat.index', [
            'messages' => $messages,
            'suggestions' => $suggestions,
            'quickReplies' => $quickReplies,
            'maxLength' => (int) config('chatbot.max_message_length', 500),
        ]);
    }

    public function store(Request $request, ChatDriver $driver): JsonResponse
    {
        $max = (int) config('chatbot.max_message_length', 500);
        $request->merge([
            'message' => trim((string) $request->input('message', '')),
        ]);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:'.$max],
        ]);

        $user = $request->user();

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        $result = $driver->reply($user, $validated['message']);

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $result->message,
            'meta' => $result->toArray(),
        ]);

        return response()->json($result->toArray());
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        ChatMessage::query()->where('user_id', $user->id)->delete();
        session()->forget(ChatContext::LAST_ORDER);

        return response()->json([
            'ok' => true,
            'quickReplies' => Suggestions::for($user),
        ]);
    }
}
