<?php

declare(strict_types=1);

namespace DbChatbot\Http\Controllers;

use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;
use DbChatbot\Models\Product;
use DbChatbot\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    public function __invoke(Request $request, Report $report): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $user->id === $report->user_id, 403);
        abort_unless($this->allowed($user, $report), 403);
        abort_unless($this->safePath($report->path), 404);
        abort_unless(Storage::disk('reports')->exists($report->path), 404);

        return Storage::disk('reports')->download($report->path, $report->downloadName());
    }

    private function allowed(ChatUser $user, Report $report): bool
    {
        $meta = $report->meta ?? [];

        return match ($report->kind) {
            'order' => $this->orderAllowed($user, $meta),
            'orders' => $user->can('viewAny', Order::class),
            'sales' => $user->can('viewSales'),
            'low_stock' => $user->can('viewAny', Product::class),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function orderAllowed(ChatUser $user, array $meta): bool
    {
        if (! isset($meta['order_id']) || ! is_numeric($meta['order_id'])) {
            return false;
        }

        $order = Order::query()->find((int) $meta['order_id']);

        return $order !== null && $user->can('view', $order);
    }

    private function safePath(string $path): bool
    {
        return $path !== ''
            && ! str_contains($path, '..')
            && ! str_contains($path, '/')
            && ! str_contains($path, '\\');
    }
}
