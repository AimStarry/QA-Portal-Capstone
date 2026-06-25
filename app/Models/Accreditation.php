<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accreditation extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'accrediting_body',
        'type',
        'level_or_tier',
        'last_visit',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'last_visit' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the program that owns the accreditation.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
