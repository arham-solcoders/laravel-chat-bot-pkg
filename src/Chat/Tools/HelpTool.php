<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;

final class HelpTool implements Tool
{
    public function name(): string
    {
        return 'help';
    }

    public function description(): string
    {
        return 'List the questions available for the signed-in user\'s role.';
    }

    public function patterns(): array
    {
        return ['help', 'what can I ask?'];
    }

    public function matches(string $message): ?array
    {
        $message = TextNormalizer::normalize($message);

        $explicit = [
            'help',
            'help me',
            'help please',
            'what can i ask',
            'what can i say',
            'what can i do',
            'commands',
            'options',
        ];

        if (! in_array($message, $explicit, true)) {
            return null;
        }

        return [];
    }

    public function handle(ChatUser $user, array $args): ToolResult
    {
        $replies = Suggestions::for($user);
        $intro = ($args['fallback'] ?? false) === true
            ? 'I can only answer questions I have a tool for.'
            : 'Here is what you can ask.';

        $lines = array_map(fn (string $reply): string => '• '.$reply, $replies);

        if ($user->isAdmin()) {
            $lines[] = '• Admins can also open the audit log from the menu.';
        }

        return ToolResult::text($intro."\n".implode("\n", $lines), $replies);
    }
}
