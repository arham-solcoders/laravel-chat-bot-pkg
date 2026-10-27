<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

final class Money
{
    public static function format(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
