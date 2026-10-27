<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Drivers;

use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;

/**
 * Chat backends turn a message into a tool result.
 * RuleBasedDriver is the default. An LLM driver can implement this later
 * and call the same tools instead of replacing them.
 */
interface ChatDriver
{
    public function reply(ChatUser $user, string $message): ToolResult;
}
