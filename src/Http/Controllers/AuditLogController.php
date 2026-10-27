<?php

declare(strict_types=1);

namespace DbChatbot\Http\Controllers;

use DbChatbot\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tool' => ['nullable', 'string', 'max:100'],
            'user' => ['nullable', 'string', 'max:255'],
            'success' => ['nullable', 'in:0,1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when(($filters['tool'] ?? '') !== '', fn (Builder $query) => $query->where('tool', $filters['tool']))
            ->when(($filters['user'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $safe = addcslashes(trim((string) $filters['user']), '%_\\');
                $query->whereHas('user', function (Builder $users) use ($safe): void {
                    $users->where('email', 'like', '%'.$safe.'%')
                        ->orWhere('name', 'like', '%'.$safe.'%');
                });
            })
            ->when(isset($filters['success']), fn (Builder $query) => $query->where('success', $filters['success'] === '1'))
            ->when(isset($filters['from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('db-chatbot::admin.audit', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
