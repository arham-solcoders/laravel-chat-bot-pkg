<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Tools;

use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /**
     * Example phrases this tool understands.
     *
     * @return list<string>
     */
    public function patterns(): array;

    /**
     * Return extracted arguments when the message matches, or null.
     *
     * @return array<string, mixed>|null
     */
    public function matches(string $message): ?array;

    /**
     * @param  array<string, mixed>  $args
     */
    public function handle(ChatUser $user, array $args): ToolResult;
}
