<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Drivers;

use DbChatbot\Chat\AuditLogger;
use DbChatbot\Chat\ChatContext;
use DbChatbot\Chat\IntentDetector;
use DbChatbot\Chat\Suggestions;
use DbChatbot\Chat\ToolResult;
use DbChatbot\Contracts\ChatUser;
use Throwable;

final class RuleBasedDriver implements ChatDriver
{
    public function __construct(
        private IntentDetector $detector,
        private AuditLogger $audit,
    ) {}

    public function reply(ChatUser $user, string $message): ToolResult
    {
        $message = trim($message);
        $limit = (int) config('chatbot.max_message_length', 500);

        if ($message === '' || mb_strlen($message) > $limit) {
            $this->audit->log($user, 'rejected', ['length' => mb_strlen($message)], false);

            return ToolResult::text(
                $message === '' ? 'Type a question to get started.' : 'That message is too long.',
                Suggestions::for($user),
                false,
            );
        }

        $intent = $this->detector->detect($message);

        try {
            $result = $intent->tool->handle($user, $intent->args);
        } catch (Throwable $exception) {
            report($exception);
            $result = ToolResult::text(
                'Something went wrong. Try a different question.',
                Suggestions::for($user),
                false,
            );
        }

        $this->audit->log($user, $intent->tool->name(), $intent->args, $result->success);

        $orderNumber = $result->context['last_order_number'] ?? null;

        if (is_numeric($orderNumber)) {
            session([ChatContext::LAST_ORDER => (int) $orderNumber]);
        }

        return $result;
    }
}
