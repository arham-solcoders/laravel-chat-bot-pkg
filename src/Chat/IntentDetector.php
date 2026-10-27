<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

use DbChatbot\Chat\Support\EntityExtractor;
use DbChatbot\Chat\Support\TextNormalizer;
use DbChatbot\Chat\Tools\HelpTool;
use DbChatbot\Chat\Tools\Tool;

final class IntentDetector
{
    public function __construct(private ToolRegistry $registry) {}

    public function detect(string $message): Intent
    {
        $normalized = TextNormalizer::normalize($message);
        $entities = EntityExtractor::extract($normalized);

        foreach ($this->registry->all() as $tool) {
            $matched = $tool->matches($normalized);

            if ($matched === null) {
                continue;
            }

            $args = array_merge($entities, $matched);

            if (($args['follow_up'] ?? false) === true && ! isset($args['order_number'])) {
                $last = session(ChatContext::LAST_ORDER);

                if (is_numeric($last)) {
                    $args['order_number'] = (int) $last;
                }
            }

            return new Intent($tool, $args);
        }

        return new Intent($this->helpTool(), array_merge($entities, ['fallback' => true]));
    }

    private function helpTool(): Tool
    {
        foreach ($this->registry->all() as $tool) {
            if ($tool->name() === 'help') {
                return $tool;
            }
        }

        return app(HelpTool::class);
    }
}
