<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\AuditLog;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function log(ChatUser $user, string $tool, array $args, bool $success): void
    {
        AuditLog::query()->create([
            'user_id' => $user->id,
            'tool' => $tool,
            'args' => $this->sanitize($args),
            'success' => $success,
        ]);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function sanitize(array $args): array
    {
        $clean = [];

        foreach ($args as $key => $value) {
            if (preg_match('/password|token|remember|card|cvv|pan|secret/i', (string) $key) === 1) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
                $clean[(string) $key] = $value;
            }
        }

        return $clean;
    }
}
