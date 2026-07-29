<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_record_id',
        'program_id',
        'responsible_unit_id',
        'school_name',
        'status',
        'approval_state',
        'document_link',
        'pending_document_link',
        'action_plan',
        'rejection_reason',
        'workflow_stage',
    ];

    /**
     * Get the compliance record for this assignment.
     */
    public function complianceRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRecord::class, 'compliance_record_id', 'compliance_record_id');
    }

    /**
     * Get the program for this assignment.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * Get the responsible unit for this assignment.
     */
    public function responsibleUnit(): BelongsTo
    {
        return $this->belongsTo(ResponsibleUnit::class, 'responsible_unit_id', 'responsible_unit_id');
    }
}
