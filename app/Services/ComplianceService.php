<?php

namespace App\Services;

use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use App\Models\RecommendationItem;
use App\Models\ResponsibleUnit;
use App\Models\Program;
use App\Models\User;
use Illuminate\Validation\Rule;

class ComplianceService
{
    /**
     * Returns the shared validation rules used in both store() and update().
     */
    public function validationRules(): array
    {
        return [
            'program_id'             => 'nullable|exists:programs,program_id',
            'program_ids'            => 'nullable|array',
            'program_ids.*'          => 'nullable',
            'responsible_unit_id'    => 'nullable|exists:responsible_units,responsible_unit_id',
            'responsible_unit_ids'   => 'nullable|array',
            'responsible_unit_ids.*' => 'nullable',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'status'                 => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'priority'               => ['nullable', Rule::in(['Critical', 'High', 'Medium', 'Low'])],
            'due_date'               => 'nullable|date',
            'responsible_unit'       => 'nullable|string|max:255',
            'contact_person'         => 'nullable|string|max:255',
            'contact_email'          => 'nullable|email|max:255',
            'document_link'          => 'nullable|url',
            'accrediting_body'       => 'required|string|max:255',
            'school'                 => 'nullable|string|max:255',
            'schools'                => 'nullable|array',
            'schools.*'              => 'nullable|string|max:255',
            'recommendation'         => 'nullable|string',
            'recommendations'        => 'required|array|min:1',
            'recommendations.*'      => 'required|string|max:1000',
            'categories'             => 'required|array|min:1',
            'categories.*'           => 'required|string|max:255',
            'areas'                  => 'required|array|min:1',
            'areas.*'                => 'required|string|max:255',
            'action_plan'            => 'nullable|string',
            'visit_date'             => 'nullable|date',
        ];
    }

    /**
     * Normalise the validated payload.
     *
     * @return array{0: array, 1: int[], 2: int[], 3: bool, 4: string[]}
     */
    public function processPayload(array $validated, array $rawUnitIds): array
    {
        if (isset($validated['schools']) && is_array($validated['schools'])) {
            $schools = array_values(array_filter(array_map('trim', $validated['schools'])));
            $validated['school'] = !empty($schools) ? implode('; ', $schools) : 'General';
        }
        if (empty($validated['school'])) {
            $validated['school'] = 'General';
        }
        unset($validated['schools']);

        $programIds = [];
        if (!empty($validated['program_ids'])) {
            $programIds = array_values(array_unique(array_filter(array_map('intval', $validated['program_ids']))));
        } elseif (!empty($validated['program_id'])) {
            $programIds = [(int) $validated['program_id']];
        }

        $isAllUnits = is_array($rawUnitIds) && (in_array('all', $rawUnitIds) || in_array('ALL_UNITS', $rawUnitIds));
        $unitIds    = [];
        if ($isAllUnits) {
            $unitIds = ResponsibleUnit::pluck('responsible_unit_id')->toArray();
        } elseif (!empty($validated['responsible_unit_ids'])) {
            $unitIds = array_values(array_unique(array_filter(array_map('intval', $validated['responsible_unit_ids']))));
        } elseif (!empty($validated['responsible_unit_id'])) {
            $unitIds = [(int) $validated['responsible_unit_id']];
        }

        unset($validated['program_ids'], $validated['responsible_unit_ids']);

        $validated['program_id']          = $programIds[0] ?? null;
        $validated['responsible_unit_id'] = $unitIds[0] ?? null;

        if ($isAllUnits) {
            $validated['responsible_unit'] = 'All Departments & Units';
        } elseif (!empty($unitIds)) {
            $names = ResponsibleUnit::whereIn('responsible_unit_id', $unitIds)->pluck('name')->toArray();
            if (!empty($names)) {
                $validated['responsible_unit'] = implode('; ', $names);
            }
        } elseif (!empty($validated['responsible_unit_id'])) {
            $ru = ResponsibleUnit::find($validated['responsible_unit_id']);
            if ($ru) {
                $validated['responsible_unit'] = $ru->name;
            }
        }

        $validated['category'] = implode(', ', array_map('trim', $validated['categories']));
        $validated['area']     = implode(', ', array_map('trim', $validated['areas']));
        unset($validated['categories'], $validated['areas']);

        $recommendations             = $validated['recommendations'];
        $validated['recommendation'] = implode('; ', $recommendations);
        unset($validated['recommendations']);

        return [$validated, $programIds, $unitIds, $isAllUnits, $recommendations];
    }

    /**
     * Sync school, program, and unit assignments for a compliance record using N x M combination matrix.
     */
    public function syncAssignments(
        ComplianceRecord $compliance,
        array $programIds,
        array $unitIds,
        bool $isAllUnits,
        array $validated,
        bool $isCreate = false
    ): void {
        $status        = $validated['status'];
        $approvalState = $validated['approval_state'] ?? 'None';
        $docLink       = ($approvalState !== 'Pending Approval') ? ($validated['document_link'] ?? null) : null;
        $pendingDoc    = ($approvalState === 'Pending Approval') ? ($validated['document_link'] ?? null) : null;
        $workflowStage = $validated['workflow_stage'] ?? 'recommendation_created';

        $schoolsList = [];
        if (!empty($validated['school']) && $validated['school'] !== 'General') {
            $schoolsList = array_values(array_filter(array_map('trim', explode(';', $validated['school']))));
        }

        // Build desired target matrix pairs: [ ['school_name' => ..., 'responsible_unit_id' => ..., 'program_id' => ...] ]
        $targetPairs = [];

        if (!empty($schoolsList) && !empty($unitIds)) {
            // Case 1: N Schools x M Units (Cartesian Matrix)
            foreach ($schoolsList as $sName) {
                foreach ($unitIds as $uId) {
                    $targetPairs[] = [
                        'school_name'         => $sName,
                        'responsible_unit_id' => $uId,
                        'program_id'          => null,
                    ];
                }
            }
        } elseif (!empty($schoolsList) && empty($unitIds)) {
            // Case 2: N Schools only (Unit self-reported by school)
            foreach ($schoolsList as $sName) {
                $targetPairs[] = [
                    'school_name'         => $sName,
                    'responsible_unit_id' => $validated['responsible_unit_id'] ?? null,
                    'program_id'          => null,
                ];
            }
        } elseif (empty($schoolsList) && !empty($unitIds)) {
            // Case 3: M Units only (Institutional / General school)
            $generalSchool = !empty($validated['school']) ? $validated['school'] : 'General';
            foreach ($unitIds as $uId) {
                $targetPairs[] = [
                    'school_name'         => $generalSchool,
                    'responsible_unit_id' => $uId,
                    'program_id'          => null,
                ];
            }
        }

        // Also incorporate specific program targets if provided
        if (!empty($programIds)) {
            if (!empty($unitIds)) {
                foreach ($programIds as $pId) {
                    foreach ($unitIds as $uId) {
                        $targetPairs[] = [
                            'school_name'         => null,
                            'responsible_unit_id' => $uId,
                            'program_id'          => $pId,
                        ];
                    }
                }
            } else {
                foreach ($programIds as $pId) {
                    $targetPairs[] = [
                        'school_name'         => null,
                        'responsible_unit_id' => $validated['responsible_unit_id'] ?? null,
                        'program_id'          => $pId,
                    ];
                }
            }
        }

        if ($isCreate) {
            foreach ($targetPairs as $pair) {
                $compliance->assignments()->create([
                    'school_name'           => $pair['school_name'],
                    'responsible_unit_id'   => $pair['responsible_unit_id'],
                    'program_id'            => $pair['program_id'],
                    'status'                => $status,
                    'approval_state'        => $approvalState,
                    'document_link'         => $docLink,
                    'pending_document_link'  => $pendingDoc,
                    'workflow_stage'        => $workflowStage,
                ]);
            }
        } else {
            $existingAssignments = $compliance->assignments()->get();
            $keptAssignmentIds = [];

            foreach ($targetPairs as $pair) {
                // Find existing match
                $existing = $existingAssignments->first(function ($a) use ($pair) {
                    $schoolMatches = (string)$a->school_name === (string)$pair['school_name'];
                    $unitMatches   = (int)$a->responsible_unit_id === (int)$pair['responsible_unit_id'];
                    $progMatches   = (int)$a->program_id === (int)$pair['program_id'];
                    return $schoolMatches && $unitMatches && $progMatches;
                });

                if ($existing) {
                    $keptAssignmentIds[] = $existing->id;
                } else {
                    $newAss = $compliance->assignments()->create([
                        'school_name'           => $pair['school_name'],
                        'responsible_unit_id'   => $pair['responsible_unit_id'],
                        'program_id'            => $pair['program_id'],
                        'status'                => $status,
                        'approval_state'        => 'None',
                        'document_link'         => $validated['document_link'] ?? null,
                        'workflow_stage'        => $workflowStage,
                    ]);
                    $keptAssignmentIds[] = $newAss->id;
                }
            }

            // Remove assignments no longer present in target pairs
            $compliance->assignments()->whereNotIn('id', $keptAssignmentIds)->delete();
        }
    }

    /**
     * Smart-sync recommendation items by text.
     */
    public function syncRecommendationItems(ComplianceRecord $compliance, array $texts): void
    {
        $texts = array_values(array_filter(array_map('trim', $texts)));

        $existing = $compliance->recommendationItems()->get()->keyBy('text');
        $incoming = collect($texts);

        $toDelete = $existing->keys()->diff($incoming);
        if ($toDelete->isNotEmpty()) {
            $compliance->recommendationItems()->whereIn('text', $toDelete->toArray())->delete();
        }

        $toCreate = $incoming->diff($existing->keys());
        foreach ($toCreate as $text) {
            $compliance->recommendationItems()->create([
                'text'         => $text,
                'is_completed' => false,
            ]);
        }
    }

    /**
     * Create all recommendation items fresh.
     */
    public function createRecommendationItems(ComplianceRecord $compliance, array $texts): void
    {
        foreach ($texts as $text) {
            $text = trim($text);
            if (!empty($text)) {
                $compliance->recommendationItems()->create([
                    'text'         => $text,
                    'is_completed' => false,
                ]);
            }
        }
    }

    /**
     * Resolve the most appropriate contact user for a given validated payload.
     */
    public function resolveContactUser(array $validated): ?User
    {
        $assignedUser = null;

        if (!empty($validated['responsible_unit_id'])) {
            $ru = ResponsibleUnit::with(['users', 'parent.users'])->find($validated['responsible_unit_id']);
            if ($ru) {
                $assignedUser = $ru->users->first()
                    ?? User::where('responsible_unit_id', $ru->responsible_unit_id)->first()
                    ?? ($ru->unit_id ? User::where('unit_id', $ru->unit_id)->first() : null)
                    ?? ($ru->parent ? $ru->parent->users->first() : null);
            }
        }

        if (!$assignedUser && !empty($validated['responsible_unit'])) {
            $ru = ResponsibleUnit::where('name', $validated['responsible_unit'])
                ->orWhere('code', $validated['responsible_unit'])
                ->first();
            if ($ru) {
                $assignedUser = $ru->users->first()
                    ?? User::where('responsible_unit_id', $ru->responsible_unit_id)->first()
                    ?? ($ru->unit_id ? User::where('unit_id', $ru->unit_id)->first() : null)
                    ?? ($ru->parent ? $ru->parent->users->first() : null);
            }
        }

        return $assignedUser;
    }
}
