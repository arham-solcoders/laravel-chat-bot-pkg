<?php

declare(strict_types=1);

namespace DbChatbot\Policies;

use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;

class OrderPolicy
{
    public function viewAny(ChatUser $user): bool
    {
        return $user->managesOrders();
    }

    public function view(ChatUser $user, Order $order): bool
    {
        if ($user->managesOrders()) {
            return true;
        }

        return $order->customer?->user_id === $user->id;
    }
}
