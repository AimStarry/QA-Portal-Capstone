<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_code',
        'program_name',
        'college',
        'program_level',
    ];

    /**
     * Get the accreditations for the program.
     */
    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class);
    }

    /**
     * Get the compliance records for the program.
     */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    /**
     * Get the risk items for the program.
     */
    public function riskItems(): HasMany
    {
        return $this->hasMany(RiskItem::class);
    }

    /**
     * Get the graduate records for the program.
     */
    public function graduateRecords(): HasMany
    {
        return $this->hasMany(GraduateRecord::class);
    }
}
