<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsibleUnit extends Model
{
    protected $table = 'responsible_units';
    protected $primaryKey = 'responsible_unit_id';

    protected $fillable = [
        'name',
        'code',
        'college_id',
        'unit_id',
    ];

    /**
     * Get the college associated with the responsible unit.
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    /**
     * Get the unit associated with the responsible unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }

    /**
     * Get the laboratories/categories under this unit.
     */
    public function laboratories(): HasMany
    {
        return $this->hasMany(Laboratory::class, 'responsible_unit_id', 'responsible_unit_id');
    }

    /**
     * Get the users bound to this unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'responsible_unit_id', 'responsible_unit_id');
    }
}
