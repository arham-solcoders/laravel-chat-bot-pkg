<?php

declare(strict_types=1);

namespace DbChatbot\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
}
