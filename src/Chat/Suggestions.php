<?php

declare(strict_types=1);

namespace DbChatbot\Chat;

use DbChatbot\Contracts\ChatUser;

final class Suggestions
{
    /**
     * @return list<string>
     */
    public static function for(ChatUser $user): array
    {
        if ($user->managesOrders()) {
            return [
                'Pending orders',
                'Shipped orders this week',
                'Low stock',
                'Out of stock products',
                'Sales today',
                'Sales this week',
                'Sales this month',
                'Help',
            ];
        }

        return [
            'My orders',
            'Recent orders',
            'Help',
        ];
    }
}
