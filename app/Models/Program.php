<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasCustomPrimaryKey;

class Program extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'program_id';

    protected $fillable = [
        'program_code',
        'program_name',
        'former_name',
        'college_id',
        'department',
        'former_department',
        'program_level',
        'is_accreditable',
    ];
    protected $casts = [
        'is_accreditable' => 'boolean',
    ];
    /**
     * Get the school/college that owns the program.
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    /**
     * Get the accreditations for the program.
     */
    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class, 'program_id', 'program_id');
    }

    /**
     * Get the compliance records for the program.
     */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class, 'program_id', 'program_id');
    }

    /**
     * Get the compliance assignments for the program.
     */
    public function complianceAssignments(): HasMany
    {
        return $this->hasMany(ComplianceAssignment::class, 'program_id', 'program_id');
    }

    /**
     * Get the risk items for the program.
     */
    public function riskItems(): HasMany
    {
        return $this->hasMany(RiskItem::class, 'program_id', 'program_id');
    }

    /**
     * Get the graduate records for the program.
     */
    public function graduateRecords(): HasMany
    {
        return $this->hasMany(GraduateRecord::class, 'program_id', 'program_id');
    }
}