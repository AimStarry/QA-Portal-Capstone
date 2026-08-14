<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use App\Models\ResponsibleUnit;
use App\Models\Program;
use App\Models\College;
use App\Models\User;

class UpdateComplianceSeeder extends Seeder
{
    public function run()
    {
        $units = ResponsibleUnit::all()->keyBy('name');

        $getUnitId = function($name) use ($units) {
            if (isset($units[$name])) {
                return $units[$name]->responsible_unit_id;
            }
            $u = ResponsibleUnit::where('name', 'like', "%{$name}%")->first();
            return $u ? $u->responsible_unit_id : null;
        };

        $records = ComplianceRecord::with(['program', 'assignments.program'])->get();

        foreach ($records as $record) {
            // Remove redundant parent school assignments if a program under that school is assigned (e.g. BED vs SHS)
            $hasProgram = $record->assignments()->whereNotNull('program_id')->exists();
            if ($hasProgram) {
                $record->assignments()->whereNotNull('school_name')->delete();
            }

            // Remove standalone unit assignments if specific schools or programs are targeted
            $hasTargetSchoolsOrPrograms = $record->assignments()->where(function($q) {
                $q->whereNotNull('school_name')->orWhereNotNull('program_id');
            })->exists();

            if ($hasTargetSchoolsOrPrograms) {
                $record->assignments()->whereNull('school_name')->whereNull('program_id')->delete();
            }

            // Clean contact person: do not force Dean Computing on non-SOC units or CPO!
            $unitId = $record->responsible_unit_id ?? $getUnitId($record->responsible_unit);
            $ru = $unitId ? ResponsibleUnit::with(['users'])->find($unitId) : null;
            
            $assignedUser = null;
            if ($ru && $ru->users && $ru->users->count() > 0) {
                $assignedUser = $ru->users->first();
            }

            $record->update([
                'contact_person' => $assignedUser ? $assignedUser->name : null,
                'contact_email' => $assignedUser ? ($assignedUser->email ?? ($assignedUser->username . '@hau.edu.ph')) : null,
            ]);
        }
    }
}
