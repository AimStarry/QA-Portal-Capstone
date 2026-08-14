<?php

namespace App\Console\Commands;

use App\Models\Accreditation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateAccreditationStatuses extends Command
{
    /**
     * Run this daily via the scheduler to keep accreditation statuses accurate
     * without requiring manual edits. Schedule: daily at midnight.
     */
    protected $signature = 'accreditations:update-status {--dry-run : Preview changes without persisting}';

    protected $description = 'Auto-update Accreditation statuses based on expiry dates.';

    public function handle(): int
    {
        $today    = Carbon::today();
        $in90days = Carbon::today()->addDays(90);

        $expired  = 0;
        $expiring = 0;

        // 1. Mark past-expiry as Expired
        $toExpire = Accreditation::whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->whereNotIn('status', ['Expired'])
            ->get();

        foreach ($toExpire as $a) {
            $this->line(($this->option('dry-run') ? '[DRY-RUN] ' : '') . "Expired: {$a->accrediting_body} → program #{$a->program_id} (was {$a->status})");
            if (!$this->option('dry-run')) $a->update(['status' => 'Expired']);
            $expired++;
        }

        // 2. Mark within-90-days as Expiring Soon
        $toWarn = Accreditation::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $in90days)
            ->where('status', 'Active')
            ->get();

        foreach ($toWarn as $a) {
            $this->line(($this->option('dry-run') ? '[DRY-RUN] ' : '') . "Expiring Soon: {$a->accrediting_body} → program #{$a->program_id} (expires {$a->expiry_date})");
            if (!$this->option('dry-run')) $a->update(['status' => 'Expiring Soon']);
            $expiring++;
        }

        $this->info("Done. Expired: {$expired} | Expiring Soon: {$expiring}");
        return Command::SUCCESS;
    }
}
