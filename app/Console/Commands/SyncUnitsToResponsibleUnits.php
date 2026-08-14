<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Models\ResponsibleUnit;
use Illuminate\Console\Command;

class SyncUnitsToResponsibleUnits extends Command
{
    /**
     * The name and signature of the console command.
     * Run this whenever you add new Units to ensure ResponsibleUnit entries exist.
     * This was previously (incorrectly) run on every compliance index page load.
     */
    protected $signature = 'units:sync {--dry-run : Preview changes without persisting}';

    protected $description = 'Ensure every Unit record has a matching ResponsibleUnit entry.';

    public function handle(): int
    {
        $units   = Unit::all();
        $created = 0;
        $skipped = 0;

        foreach ($units as $unit) {
            $exists = ResponsibleUnit::where('name', $unit->name)->orWhere('code', $unit->code)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            if (!$this->option('dry-run')) {
                ResponsibleUnit::create([
                    'name'    => $unit->name,
                    'code'    => $unit->code ?? null,
                    'unit_id' => $unit->unit_id,
                ]);
            }

            $created++;
            $this->line(($this->option('dry-run') ? '[DRY-RUN] ' : '') . "Created: {$unit->name}");
        }

        $this->info("Sync complete. Created: {$created} | Skipped (already exists): {$skipped}");
        return Command::SUCCESS;
    }
}
