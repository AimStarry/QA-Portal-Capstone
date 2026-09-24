<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_record_id',
        'program_id',
        'responsible_unit_id',
        'school_name',
        'status',
        'approval_state',
        'document_link',
        'pending_document_link',
        'action_plan',
        'rejection_reason',
        'workflow_stage',
    ];

    /**
     * Get the compliance record for this assignment.
     */
    public function complianceRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRecord::class, 'compliance_record_id', 'compliance_record_id');
    }

    /**
     * Get the program for this assignment.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * Get the responsible unit for this assignment.
     */
    public function responsibleUnit(): BelongsTo
    {
        return $this->belongsTo(ResponsibleUnit::class, 'responsible_unit_id', 'responsible_unit_id');
    }

    /**
     * Check if this cell submission is fully approved / compliant.
     */
    public function isCompliant(): bool
    {
        return $this->status === 'Compliant' && $this->approval_state !== 'Pending Approval';
    }

    /**
     * Check if this cell submission is pending QA Admin approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_state === 'Pending Approval';
    }

    /**
     * Check if this cell submission has been rejected and needs revision.
     */
    public function isRejected(): bool
    {
        return $this->approval_state === 'Rejected';
    }

    /**
     * Get the effective evidence link (approved document link or pending document link).
     */
    public function getEffectiveLink(): ?string
    {
        return $this->document_link ?: $this->pending_document_link;
    }

    /**
     * Check if a given user is authorized to submit evidence for this assignment.
     *
     * Rules:
     * - QA Admin: can submit/approve across all assignments.
     * - Units & Offices: can submit to assignments assigned to their unit/office across any targeted school/department.
     * - School Departments / Deans: can ONLY submit to assignments for their own department, school, or programs.
     */
    public function canUserSubmit(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->usertype === 'QA Admin') {
            return true;
        }

        $userRespUnitId = $user->responsible_unit_id;
        $userUnitId     = $user->unit_id;
        $userCollegeId  = $user->college_id;

        if (!$userCollegeId && $userRespUnitId) {
            $userCollegeId = ResponsibleUnit::where('responsible_unit_id', $userRespUnitId)->value('college_id');
        }

        // 1. Is this assignment assigned to an Administrative Office / Support Unit?
        if ($this->responsible_unit_id) {
            $ru = $this->responsibleUnit;
            $isOffice = $ru && ($ru->unit_id !== null || $ru->college_id === null);

            if ($isOffice) {
                // Administrative office staff can submit if it matches their unit
                if ($userRespUnitId && (int)$this->responsible_unit_id === (int)$userRespUnitId) {
                    return true;
                }
                if ($userUnitId && $ru && (int)$ru->unit_id === (int)$userUnitId) {
                    return true;
                }
                // Academic school users cannot submit for an external administrative office's column
                return false;
            }
        }

        // 2. School Department / Dean / College authorization:
        // User can ONLY submit for their own department, school, or programs.
        if ($userCollegeId) {
            $userCollege = College::find($userCollegeId);
            $collegeName = $userCollege?->name;
            $collegeCode = $userCollege?->code;

            // Check if school_name matches this user's college
            if (!empty($this->school_name)) {
                $trimmedSchool = trim($this->school_name);
                if (($collegeName && strcasecmp($trimmedSchool, trim($collegeName)) === 0) ||
                    ($collegeCode && strcasecmp($trimmedSchool, trim($collegeCode)) === 0)) {
                    
                    // If assignment is for a specific department under that school:
                    if ($this->responsible_unit_id && $userRespUnitId) {
                        if ((int)$this->responsible_unit_id === (int)$userRespUnitId) {
                            return true;
                        }
                        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
                            $ru = $this->responsibleUnit;
                            if ($ru && (int)$ru->college_id === (int)$userCollegeId) {
                                return true;
                            }
                        }
                        return false;
                    }
                    return true;
                }
            }

            // Check if program matches user's college
            if ($this->program_id && $this->program) {
                if ((int)$this->program->college_id === (int)$userCollegeId) {
                    return true;
                }
            }

            // Check if responsible unit is in user's college
            if ($this->responsible_unit_id) {
                $ru = $this->responsibleUnit;
                if ($ru && (int)$ru->college_id === (int)$userCollegeId) {
                    if (!$userRespUnitId || (int)$userRespUnitId === (int)$this->responsible_unit_id || $user->usertype === 'Dean' || $user->usertype === 'Principal') {
                        return true;
                    }
                }
            }

            // Outside this user's school/college/program -> denied!
            return false;
        }

        // 3. Fallback for office user matching responsible unit
        if ($userRespUnitId && (int)$this->responsible_unit_id === (int)$userRespUnitId) {
            return true;
        }

        return false;
    }
}
