<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laboratory extends Model
{
    protected $table = 'laboratories';
    protected $primaryKey = 'laboratory_id';

    protected $fillable = [
        'name',
        'responsible_unit_id',
    ];

    /**
     * Get the responsible unit that owns the laboratory.
     */
    public function responsibleUnit(): BelongsTo
    {
        return $this->belongsTo(ResponsibleUnit::class, 'responsible_unit_id', 'responsible_unit_id');
    }
}
