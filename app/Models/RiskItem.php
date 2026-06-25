<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'description',
        'likelihood',
        'impact',
        'mitigation_plan',
        'status',
    ];

    /**
     * Get the program associated with the risk item.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
