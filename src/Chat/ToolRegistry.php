<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

use DbChatbot\Chat\Tools\Tool;
use RuntimeException;

final class ToolRegistry
{
    /**
     * @return list<Tool>
     */
    public function all(): array
    {
        $tools = [];

        foreach (config('chatbot.tools', []) as $class) {
            $tool = app($class);

            if (! $tool instanceof Tool) {
                throw new RuntimeException($class.' must implement '.Tool::class.'.');
            }

            $tools[] = $tool;
        }

        return $tools;
    }
}
