<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasCustomPrimaryKey;

class ComplianceRecord extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'compliance_record_id';

    protected $fillable = [
        'program_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'responsible_unit',
        'responsible_unit_id',
        'contact_person',
        'contact_email',
        'document_link',
        'pending_status',
        'pending_document_link',
        'approval_state',
        'rejection_reason',
        'accrediting_body',
        'school',
        'recommendation',
        'category',
        'area',
        'action_plan',
        'visit_date',
        'workflow_stage',
    ];

    protected $casts = [
        'due_date' => 'date',
        'visit_date' => 'date',
    ];

    /**
     * Get the responsible unit associated with the compliance record.
     */
    public function responsibleUnitRelation(): BelongsTo
    {
        return $this->belongsTo(ResponsibleUnit::class, 'responsible_unit_id', 'responsible_unit_id');
    }


    /**
     * Get the assignments (departments/programs) for this compliance record.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ComplianceAssignment::class, 'compliance_record_id', 'compliance_record_id');
    }

    /**
     * Get the program associated with the compliance record (legacy / primary).
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * Get the recommendation checklist items for this compliance record.
     */
    public function recommendationItems(): HasMany
    {
        return $this->hasMany(RecommendationItem::class, 'compliance_record_id', 'compliance_record_id');
    }

    /**
     * Get the count of completed recommendation items.
     */
    public function completedRecommendationsCount(): int
    {
        return $this->recommendationItems()->where('is_completed', true)->count();
    }

    /**
     * Get the total number of recommendation items.
     */
    public function totalRecommendations(): int
    {
        return $this->recommendationItems()->count();
    }

    /**
     * Calculate the completion rate (0-100) based on recommendation checklist items.
     */
    public function completionRate(): int
    {
        $total = $this->totalRecommendations();
        if ($total === 0) return 0;
        return (int) round(($this->completedRecommendationsCount() / $total) * 100);
    }
}
