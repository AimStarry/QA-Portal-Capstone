<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed department-level responsible units under each school.
     * Format: "School Name — Department Name"
     */
    public function up(): void
    {
        $departments = [
            // school name => [ [dept name, code], ... ]
            'School of Arts and Sciences' => [
                ['Department of Philosophy, Culture, and Art', 'SAS-PCA'],
                ['Department of Natural and Applied Sciences', 'SAS-NAS'],
                ['Department of Social Sciences and Communication', 'SAS-SSC'],
                ['Department of Languages and Literature', 'SAS-LL'],
            ],
            'School of Business and Accountancy' => [
                ['Department of Accountancy', 'SBA-ACC'],
                ['Department of Business Administration', 'SBA-BUS'],
                ['Department of Management', 'SBA-MGT'],
            ],
            'College of Criminal Justice Education and Forensic Sciences' => [
                ['Department of Criminology', 'CCJEF-CRIM'],
                ['Department of Forensic Science', 'CCJEF-FOR'],
            ],
            'School of Education' => [
                ['Department of Elementary Education', 'SED-ELEM'],
                ['Department of Secondary Education', 'SED-SEC'],
                ['Department of Physical Education', 'SED-PE'],
            ],
            'School of Engineering and Architecture' => [
                ['Department of Civil Engineering', 'SEA-CE'],
                ['Department of Electrical Engineering', 'SEA-EE'],
                ['Department of Electronics Engineering', 'SEA-ECE'],
                ['Department of Mechanical Engineering', 'SEA-ME'],
                ['Department of Computer Engineering', 'SEA-CPE'],
                ['Department of Aeronautical Engineering', 'SEA-AERO'],
                ['Department of Architecture', 'SEA-ARCH'],
            ],
            'School of Hospitality and Tourism Management' => [
                ['Department of Hotel and Restaurant Management', 'SHTM-HRM'],
                ['Department of Tourism Management', 'SHTM-TM'],
            ],
            'School of Nursing and Allied Medical Sciences' => [
                ['Department of Nursing', 'SNAMS-NUR'],
                ['Department of Radiologic Technology', 'SNAMS-RT'],
                ['Department of Medical Technology', 'SNAMS-MT'],
            ],
            'School of Computing' => [
                ['Department of Computer Science', 'SOC-CS'],
                ['Department of Information Technology', 'SOC-IT'],
                ['Department of Cybersecurity', 'SOC-CYB'],
                ['Department of Entertainment and Multimedia Computing', 'SOC-EMC'],
            ],
            'Basic Education' => [
                ['Laboratory Elementary School Department', 'BED-LES'],
                ['Junior High School Department', 'BED-JHS'],
                ['Senior High School Department', 'BED-SHS'],
            ],
        ];

        $now = now();

        foreach ($departments as $schoolName => $depts) {
            // Find the parent (school-level) responsible unit
            $parent = DB::table('responsible_units')->where('name', $schoolName)->first();
            if (!$parent) continue;

            // Find the college_id from the parent
            $collegeId = $parent->college_id;

            foreach ($depts as [$deptName, $deptCode]) {
                // Check if already exists (idempotent)
                $exists = DB::table('responsible_units')->where('name', $deptName)->exists();
                if ($exists) continue;

                DB::table('responsible_units')->insert([
                    'name'            => $deptName,
                    'code'            => $deptCode,
                    'college_id'      => $collegeId,
                    'unit_id'         => null,
                    'parent_unit_id'  => $parent->responsible_unit_id,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove all department-level entries (those with a parent_unit_id set)
        DB::table('responsible_units')->whereNotNull('parent_unit_id')->delete();
    }
};
