<?php

declare(strict_types=1);

namespace DbChatbot\Policies;

use DbChatbot\Contracts\ChatUser;

class ProductPolicy
{
    public function viewAny(ChatUser $user): bool
    {
        return $user->managesOrders();
    }
}
