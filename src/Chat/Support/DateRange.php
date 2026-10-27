<?php

declare(strict_types=1);

namespace DbChatbot\Chat\Support;

use Illuminate\Support\Carbon;

final class DateRange
{
    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function bounds(?string $label): array
    {
        $now = now();

        return match ($label) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            default => [null, null],
        };
    }

    public static function title(?string $label): string
    {
        return match ($label) {
            'today' => 'today',
            'yesterday' => 'yesterday',
            'this_week' => 'this week',
            'last_week' => 'last week',
            'this_month' => 'this month',
            'last_month' => 'last month',
            'last_7_days' => 'the last 7 days',
            default => 'this month',
        };
    }
}
