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
        'parent_unit_id',
    ];

    /**
     * Guard against deleting a responsible unit that still has users or compliance assignments.
     */
    protected static function booted(): void
    {
        static::deleting(function (ResponsibleUnit $unit) {
            if ($unit->users()->exists()) {
                throw new \RuntimeException(
                    "Cannot delete unit '{$unit->name}': it still has users assigned to it. Reassign them first."
                );
            }
            if ($unit->complianceAssignments()->exists()) {
                throw new \RuntimeException(
                    "Cannot delete unit '{$unit->name}': it has existing compliance assignments. Remove them first."
                );
            }
        });
    }

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
     * Get the parent responsible unit (e.g. the school this department belongs to).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ResponsibleUnit::class, 'parent_unit_id', 'responsible_unit_id');
    }

    /**
     * Get child departments under this responsible unit (school).
     */
    public function children(): HasMany
    {
        return $this->hasMany(ResponsibleUnit::class, 'parent_unit_id', 'responsible_unit_id');
    }

    /**
     * Get the users bound to this unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'responsible_unit_id', 'responsible_unit_id');
    }

    /**
     * Get the compliance assignments for this responsible unit.
     */
    public function complianceAssignments(): HasMany
    {
        return $this->hasMany(ComplianceAssignment::class, 'responsible_unit_id', 'responsible_unit_id');
    }
}
