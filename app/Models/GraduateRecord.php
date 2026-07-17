<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCustomPrimaryKey;

class GraduateRecord extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'graduate_record_id';

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
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }
}
