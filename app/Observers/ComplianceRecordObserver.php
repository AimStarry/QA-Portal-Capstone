<?php

namespace App\Observers;

use App\Models\ComplianceRecord;

class ComplianceRecordObserver
{
    /**
     * Compliance record changes do not affect program accreditable status.
     */
    public function saved(ComplianceRecord $record)
    {
    }

    public function deleted(ComplianceRecord $record)
    {
    }
}
