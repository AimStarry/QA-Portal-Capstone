<?php

namespace App\Observers;

use App\Models\ComplianceRecord;
use App\Models\Program;

class ComplianceRecordObserver
{
    /**
     * Recalculate is_accreditable status for the given program.
     */
    private function syncProgramAccreditable($programId)
    {
        if (!$programId) return;
        $program = Program::find($programId);
        if (!$program) return;

        // If the program has at least one compliance record
        if ($program->complianceRecords()->exists()) {
            // Efficient exists query to check for non-Compliant records
            $hasDeficiencies = $program->complianceRecords()
                ->where('status', '!=', 'Compliant')
                ->exists();

            $program->update(['is_accreditable' => !$hasDeficiencies]);
        }
    }

    /**
     * Handle the ComplianceRecord "saved" event.
     */
    public function saved(ComplianceRecord $record)
    {
        $this->syncProgramAccreditable($record->program_id);
        
        // Handle cases where the parent program_id was changed/reassigned
        if ($record->isDirty('program_id')) {
            $this->syncProgramAccreditable($record->getOriginal('program_id'));
        }
    }

    /**
     * Handle the ComplianceRecord "deleted" event.
     */
    public function deleted(ComplianceRecord $record)
    {
        $this->syncProgramAccreditable($record->program_id);
    }
}
