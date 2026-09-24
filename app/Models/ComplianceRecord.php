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
     * Bootstrap the model and auto-populate contact_person and contact_email on saving if empty or placeholder.
     */
    protected static function booted(): void
    {
        static::saving(function (ComplianceRecord $record) {
            $isPlaceholder = empty($record->contact_person) 
                || $record->contact_person === 'None' 
                || strcasecmp($record->contact_person, 'bed principal') === 0 
                || strcasecmp($record->contact_person, 'bedprincipal') === 0;

            if ($isPlaceholder) {
                $resolved = $record->autoResolveContact();
                if ($resolved) {
                    $record->contact_person = $resolved['name'];
                    if (empty($record->contact_email) || strcasecmp($record->contact_email, 'bedprincipal@hau.edu.ph') === 0) {
                        $record->contact_email = $resolved['email'];
                    }
                }
            }
        });
    }

    /**
     * Accessor for contact_person: auto-resolves if empty or placeholder.
     */
    public function getContactPersonAttribute($value): ?string
    {
        $isPlaceholder = empty($value) 
            || $value === 'None' 
            || strcasecmp($value, 'bed principal') === 0 
            || strcasecmp($value, 'bedprincipal') === 0;

        if (!$isPlaceholder) {
            return $value;
        }
        $resolved = $this->autoResolveContact();
        return $resolved ? $resolved['name'] : $value;
    }

    /**
     * Accessor for contact_email: auto-resolves if empty or placeholder.
     */
    public function getContactEmailAttribute($value): ?string
    {
        $isPlaceholder = empty($value) 
            || strcasecmp($value, 'bedprincipal@hau.edu.ph') === 0;

        if (!$isPlaceholder) {
            return $value;
        }
        $resolved = $this->autoResolveContact();
        return $resolved ? $resolved['email'] : null;
    }

    /**
     * Auto-resolve contact person and email from assigned unit, program, or school.
     */
    public function autoResolveContact(): ?array
    {
        $emailDomain = config('institution.email_domain', 'hau.edu.ph');
        $user = null;

        // 1. If assigned to an administrative office or support unit
        if ($this->responsible_unit_id) {
            $ru = ResponsibleUnit::find($this->responsible_unit_id);
            if ($ru) {
                if ($ru->unit_id) {
                    $user = User::where('unit_id', $ru->unit_id)->first() ?? $ru->users->first();
                } elseif ($ru->college_id) {
                    $user = User::where('college_id', $ru->college_id)->first() ?? $ru->users->first();
                } else {
                    $user = $ru->users->first() ?? User::where('responsible_unit_id', $ru->responsible_unit_id)->first();
                }
            }
        }

        // 2. Check by program_id
        if (!$user && $this->program_id) {
            $prog = Program::find($this->program_id);
            if ($prog && $prog->college_id) {
                $user = User::where('college_id', $prog->college_id)->first();
            }
        }

        // 3. Check by school name(s)
        if (!empty($this->school) && $this->school !== 'General') {
            $schools = array_filter(array_map('trim', explode(';', $this->school)));
            $foundUsers = [];

            foreach ($schools as $sName) {
                $col = College::where('name', $sName)->orWhere('code', $sName)->first();
                if ($col) {
                    $u = User::where('college_id', $col->college_id)->first();
                    if (!$u && $col->code === 'SED') {
                        $u = User::where('email', 'anatividad@hau.edu.ph')->first();
                    }
                    if ($u && !isset($foundUsers[$u->id])) {
                        $foundUsers[$u->id] = $u;
                    }
                }
            }

            if (count($foundUsers) > 1 && (!$user || in_array($user->id, array_keys($foundUsers)))) {
                $names = array_map(fn($u) => $u->name, $foundUsers);
                $emails = array_map(fn($u) => $u->email ?? ($u->username . '@' . $emailDomain), $foundUsers);
                return [
                    'name' => implode('; ', $names),
                    'email' => implode('; ', $emails),
                ];
            } elseif (count($foundUsers) === 1 && !$user) {
                $user = reset($foundUsers);
            }
        }

        // 4. Check by responsible_unit text
        if (!$user && !empty($this->responsible_unit)) {
            $ruNames = array_filter(array_map('trim', explode(';', $this->responsible_unit)));
            foreach ($ruNames as $ruName) {
                $ru = ResponsibleUnit::where('name', $ruName)->orWhere('code', $ruName)->first();
                if ($ru) {
                    $user = ($ru->unit_id ? User::where('unit_id', $ru->unit_id)->first() : null)
                        ?? ($ru->college_id ? User::where('college_id', $ru->college_id)->first() : null)
                        ?? $ru->users->first();
                    if ($user) break;
                }
            }
        }

        if ($user) {
            return [
                'name' => $user->name,
                'email' => $user->email ?? ($user->username . '@' . $emailDomain),
            ];
        }

        return null;
    }

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

        // 2. Extract Assigned Support Units and Department Evidence Columns
        $hasDeptEvidence = false;
        $officeUnits     = [];
        $seenUnitKeys    = [];

        foreach ($assignments as $a) {
            $ru = $a->responsibleUnit;
            $isAcademicDept = false;

            if ($ru) {
                if ($ru->college_id !== null && $ru->unit_id === null) {
                    $isAcademicDept = true;
                } elseif (!empty($a->school_name) && strcasecmp(trim($ru->name), trim($a->school_name)) === 0) {
                    $isAcademicDept = true;
                }
            } elseif (empty($a->responsible_unit_id) && !empty($a->school_name)) {
                $isAcademicDept = true;
            }

            if ($isAcademicDept) {
                $hasDeptEvidence = true;
            } elseif ($ru) {
                $uid = (int) $ru->responsible_unit_id;
                if (!isset($seenUnitKeys[$uid])) {
                    $seenUnitKeys[$uid] = true;
                    $officeUnits[] = [
                        'id'      => $uid,
                        'name'    => $ru->name,
                        'code'    => $ru->code ?? $ru->name,
                        'is_dept' => false,
                    ];
                }
            }
        }

        // Fallback if no assignments yet or single general item
        if (!$hasDeptEvidence && empty($officeUnits)) {
            if ($this->responsible_unit_id && $this->responsibleUnitRelation) {
                $ru = $this->responsibleUnitRelation;
                if ($ru->college_id !== null && $ru->unit_id === null) {
                    $hasDeptEvidence = true;
                } else {
                    $officeUnits[] = [
                        'id'      => (int) $this->responsible_unit_id,
                        'name'    => $ru->name,
                        'code'    => $ru->code ?? $ru->name,
                        'is_dept' => false,
                    ];
                }
            } else {
                $hasDeptEvidence = true;
            }
        }

        // Build list of columns: Department Evidence first (if present), then Administrative Support Units
        $units = [];
        if ($hasDeptEvidence) {
            $units[] = [
                'id'      => 'dept',
                'name'    => 'Department Evidence',
                'code'    => 'DEPT',
                'is_dept' => true,
            ];
        }
        foreach ($officeUnits as $ou) {
            $units[] = $ou;
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
                $match = null;
                if (!empty($u['is_dept'])) {
                    // Match the school's own department assignment
                    $match = $assignments->first(function ($a) use ($school) {
                        $schoolMatches = false;
                        if (!empty($a->school_name)) {
                            $schoolMatches = (strcasecmp(trim($a->school_name), trim($school)) === 0);
                        } elseif ($a->program && $a->program->college) {
                            $schoolMatches = (strcasecmp(trim($a->program->college->name), trim($school)) === 0);
                        } elseif ($school === 'General') {
                            $schoolMatches = empty($a->school_name) && empty($a->program_id);
                        }

                        if (!$schoolMatches) return false;

                        $ru = $a->responsibleUnit;
                        if (!$ru) return true; // Self-reported department evidence

                        if ($ru->college_id !== null && $ru->unit_id === null) {
                            return true;
                        }
                        return (strcasecmp(trim($ru->name), trim($school)) === 0);
                    });
                } else {
                    $unitId = $u['id'];
                    $match = $assignments->first(function ($a) use ($school, $unitId) {
                        $schoolMatches = false;
                        if (!empty($a->school_name)) {
                            $schoolMatches = (strcasecmp(trim($a->school_name), trim($school)) === 0);
                        } elseif ($a->program && $a->program->college) {
                            $schoolMatches = (strcasecmp(trim($a->program->college->name), trim($school)) === 0);
                        } elseif ($school === 'General') {
                            $schoolMatches = empty($a->school_name) && empty($a->program_id);
                        }

                        return $schoolMatches && ((int) $a->responsible_unit_id === (int) $unitId);
                    });
                }

                // Fallback for non-cartesian legacy items
                if (!$match && count($units) === 1 && empty($units[0]['id'])) {
                    $match = $assignments->first(fn ($a) => !empty($a->school_name) && strcasecmp(trim($a->school_name), trim($school)) === 0);
                } elseif (!$match && count($schools) === 1 && $schools[0] === 'General') {
                    $match = $assignments->first(fn ($a) => (int) $a->responsible_unit_id === (int) ($u['id'] ?? 0));
                }

                $key = !empty($u['is_dept']) ? 'unit_dept' : ($u['id'] !== null ? "unit_{$u['id']}" : 'unit_general');
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
