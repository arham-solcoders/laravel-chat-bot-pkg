<?php

declare(strict_types=1);

namespace DbChatbot\Console\Commands;

use DbChatbot\Models\Report;
use Illuminate\Console\Command;

class PruneReports extends Command
{
    protected $signature = 'reports:prune';

    protected $description = 'Delete report files older than the configured lifetime';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('chatbot.report_ttl_hours', 24));
        $reports = Report::query()->where('created_at', '<', $cutoff)->get();

        foreach ($reports as $report) {
            $report->delete();
        }

        $this->info('Removed '.$reports->count().' old reports.');

        return self::SUCCESS;
    }
}
