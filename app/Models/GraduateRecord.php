<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraduateRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'school_year',
        'term',
        'graduates_count',
    ];

    /**
     * Get the program associated with the graduate record.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
