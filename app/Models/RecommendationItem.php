<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCustomPrimaryKey;

class RecommendationItem extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'recommendation_item_id';

    protected $fillable = [
        'compliance_record_id',
        'text',
        'is_completed',
        'completed_at',
        'evidence_link',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the compliance record this recommendation belongs to.
     */
    public function complianceRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRecord::class, 'compliance_record_id', 'compliance_record_id');
    }
}
