<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use Illuminate\Console\Command;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit-logs:cleanup {--days=90 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete audit logs older than specified number of days (default: 90)';

    /**
     * Execute the console command.
     */
    public function handle(AuditLogService $auditLogService): int
    {
        $days = (int)$this->option('days');

        if ($days <= 0) {
            $this->error('Days must be a positive integer.');
            return Command::FAILURE;
        }

        $this->info("Cleaning up audit logs older than {$days} days...");

        $result = $auditLogService->cleanupOldLogs($days);

        $this->info($result['message']);

        return Command::SUCCESS;
    }
}
