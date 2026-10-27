<?php

declare(strict_types=1);

namespace DbChatbot\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';
}
