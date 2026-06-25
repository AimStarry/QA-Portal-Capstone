<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'title',
        'description',
        'status',
        'due_date',
        'responsible_unit',
        'document_link',
        'pending_status',
        'pending_document_link',
        'approval_state',
        'rejection_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the program associated with the compliance record.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
