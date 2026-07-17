<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCustomPrimaryKey;

class Accreditation extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'accreditation_id';

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
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }
}
