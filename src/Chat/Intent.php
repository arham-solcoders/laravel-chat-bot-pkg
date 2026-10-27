<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

use DbChatbot\Chat\Tools\Tool;

final class Intent
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __construct(
        public Tool $tool,
        public array $args,
    ) {}
}
