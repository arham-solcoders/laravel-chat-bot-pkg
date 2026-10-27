<?php

declare(strict_types=1);

namespace DbChatbot\Policies;

use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Customer;

class CustomerPolicy
{
    public function viewAny(ChatUser $user): bool
    {
        return $user->managesOrders();
    }

    public function view(ChatUser $user, Customer $customer): bool
    {
        if ($user->managesOrders()) {
            return true;
        }

        return $customer->user_id === $user->id;
    }
}
