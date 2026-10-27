<?php

declare(strict_types=1);

use DbChatbot\Http\Controllers\AuditLogController;
use DbChatbot\Http\Controllers\ChatController;
use DbChatbot\Http\Controllers\ReportDownloadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('chat.store');
    Route::delete('/chat', [ChatController::class, 'destroy'])->name('chat.destroy');

    Route::get('/reports/{report}', ReportDownloadController::class)
        ->middleware('signed')
        ->name('reports.download');

    Route::get('/admin/audit', [AuditLogController::class, 'index'])
        ->middleware('can:viewAudit')
        ->name('admin.audit');
});
