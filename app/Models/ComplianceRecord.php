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

    /**
     * Get the structured Cross-Tab Matrix Grid data (Schools as rows, Assigned Units as columns).
     */
    public function getMatrixGridData(): array
    {
        $assignments = $this->assignments;
        
        // 1. Extract Target Schools list (Rows)
        $schools = [];
        foreach ($assignments as $a) {
            if (!empty($a->school_name)) {
                $schools[] = trim($a->school_name);
            } elseif ($a->program && $a->program->college) {
                $schools[] = trim($a->program->college->name);
            }
        }
        if (empty($schools) && !empty($this->school) && $this->school !== 'General') {
            $schools = array_filter(array_map('trim', explode(';', $this->school)));
        }
        $schools = array_values(array_unique(array_filter($schools)));
        if (empty($schools)) {
            $schools = ['General'];
        }

        // 2. Extract Assigned Support Units list (Columns)
        $units = [];
        $seenUnitKeys = [];
        foreach ($assignments as $a) {
            if ($a->responsible_unit_id && $a->responsibleUnit) {
                $uid = (int) $a->responsible_unit_id;
                if (!isset($seenUnitKeys[$uid])) {
                    $seenUnitKeys[$uid] = true;
                    $units[] = [
                        'id'   => $uid,
                        'name' => $a->responsibleUnit->name,
                        'code' => $a->responsibleUnit->code ?? $a->responsibleUnit->name,
                    ];
                }
            }
        }
        if (empty($units) && $this->responsible_unit_id && $this->responsibleUnitRelation) {
            $units[] = [
                'id'   => (int) $this->responsible_unit_id,
                'name' => $this->responsibleUnitRelation->name,
                'code' => $this->responsibleUnitRelation->code ?? $this->responsibleUnitRelation->name,
            ];
        }
        if (empty($units)) {
            $units[] = [
                'id'   => null,
                'name' => 'General Evidence',
                'code' => 'GEN',
            ];
        }

        // 3. Build the Grid Matrix [School => [Unit => Cell]]
        $matrix = [];
        $totalCells = 0;
        $completedCells = 0;

        foreach ($schools as $school) {
            $rowCells = [];
            $rowCompleted = 0;
            $rowTotal = count($units);

            foreach ($units as $u) {
                $unitId = $u['id'];
                
                // Find matching assignment
                $match = $assignments->first(function ($a) use ($school, $unitId) {
                    $schoolMatches = false;
                    if (!empty($a->school_name)) {
                        $schoolMatches = (strcasecmp(trim($a->school_name), trim($school)) === 0);
                    } elseif ($a->program && $a->program->college) {
                        $schoolMatches = (strcasecmp(trim($a->program->college->name), trim($school)) === 0);
                    } elseif ($school === 'General') {
                        $schoolMatches = empty($a->school_name) && empty($a->program_id);
                    }

                    $unitMatches = false;
                    if ($unitId !== null) {
                        $unitMatches = ((int) $a->responsible_unit_id === $unitId);
                    } else {
                        $unitMatches = empty($a->responsible_unit_id);
                    }

                    return $schoolMatches && $unitMatches;
                });

                // Fallback for non-cartesian legacy items
                if (!$match && count($units) === 1 && $units[0]['id'] === null) {
                    $match = $assignments->first(fn ($a) => !empty($a->school_name) && strcasecmp(trim($a->school_name), trim($school)) === 0);
                } elseif (!$match && count($schools) === 1 && $schools[0] === 'General') {
                    $match = $assignments->first(fn ($a) => (int) $a->responsible_unit_id === $unitId);
                }

                $key = $unitId !== null ? "unit_{$unitId}" : 'unit_general';
                $rowCells[$key] = $match;

                if ($match && $match->status === 'Compliant' && $match->approval_state !== 'Pending Approval') {
                    $rowCompleted++;
                    $completedCells++;
                }
                $totalCells++;
            }

            $matrix[$school] = [
                'school'          => $school,
                'cells'           => $rowCells,
                'completed_count' => $rowCompleted,
                'total_count'     => $rowTotal,
                'is_complete'     => ($rowTotal > 0 && $rowCompleted === $rowTotal),
                'rate'            => $rowTotal > 0 ? (int) round(($rowCompleted / $rowTotal) * 100) : 0,
            ];
        }

        $matrixRate = $totalCells > 0 ? (int) round(($completedCells / $totalCells) * 100) : 0;
        $hasMatrix = (count($schools) > 1 || count($units) > 1 || $assignments->count() > 1);

        return [
            'has_matrix'      => $hasMatrix,
            'schools'         => $schools,
            'units'           => $units,
            'matrix'          => $matrix,
            'total_cells'     => $totalCells,
            'completed_cells' => $completedCells,
            'matrix_rate'     => $matrixRate,
        ];
    }
}
