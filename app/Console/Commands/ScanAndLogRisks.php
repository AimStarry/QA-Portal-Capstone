<?php

namespace App\Console\Commands;

use App\Services\RiskAutoLogService;
use Illuminate\Console\Command;

class ScanAndLogRisks extends Command
{
    /**
     * Runs daily via the scheduler to auto-log and auto-resolve risks
     * from compliance records and accreditations without manual QA input.
     */
    protected $signature = 'risk:scan {--dry-run : Preview what would be logged/resolved without persisting}';

    protected $description = 'Scan compliance records and accreditations to auto-log or resolve Risk Monitor entries.';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] No changes will be persisted.');
            $this->info('Running Risk Auto-Log scan (dry run)...');
            $this->line('  (dry-run mode: skipping actual writes)');
            return Command::SUCCESS;
        }

        $this->info('Running Risk Auto-Log scan...');

        $result = RiskAutoLogService::runDailyScan();

        $this->info('Scan complete.');
        $this->line("  → New risks logged : {$result['logged']}");
        $this->line("  → Risks resolved   : {$result['resolved']}");

        return Command::SUCCESS;
    }
}
