<?php

declare(strict_types=1);

namespace DbChatbot\Contracts;

use Illuminate\Database\Eloquent\Relations\HasOne;

interface ChatUser
{
    public function isAdmin(): bool;

    public function managesOrders(): bool;

    public function customer(): HasOne;
}
