<?php

declare(strict_types=1);

namespace DbChatbot\Concerns;

use DbChatbot\Enums\Role;
use DbChatbot\Models\Customer;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait InteractsWithDbChatbot
{
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function isAdmin(): bool
    {
        return $this->chatRole() === Role::Admin;
    }

    public function managesOrders(): bool
    {
        $role = $this->chatRole();

        return $role === Role::Admin || $role === Role::Staff;
    }

    private function chatRole(): ?Role
    {
        $role = $this->role ?? null;

        if ($role instanceof Role) {
            return $role;
        }

        if (! is_string($role)) {
            return null;
        }

        return Role::tryFrom($role);
    }
}
