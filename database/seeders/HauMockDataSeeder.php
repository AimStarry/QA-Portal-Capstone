<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Program;
use App\Models\ComplianceRecord;
use App\Models\RecommendationItem;
use App\Models\RiskItem;
use App\Models\GraduateRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds mock compliance records, recommendation checklist items,
 * risk items, and graduate records using the real HAU programs.
 *
 * Run:  php artisan db:seed --class=HauMockDataSeeder
 */
class HauMockDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear tables (preserve colleges, programs, accreditations)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }

        DB::table('recommendation_items')->delete();
        DB::table('compliance_records')->delete();
        DB::table('risk_items')->delete();
        DB::table('graduate_records')->delete();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Fetch programs by code for easy reference
        $p = Program::with('college')->get()->keyBy('program_code');

        // Helper to resolve school name
        $school = fn($code) => $p[$code]?->college?->name ?? 'Unknown';

        // ── RISK ITEMS ────────────────────────────────────────────────────────

        $risks = [
            // SOC
            ['BSCS', 'Insufficient number of doctorate-degree holding CS full-time faculty may jeopardize PAASCU accreditation criteria for faculty qualifications.', 'Medium', 'High', "Sponsor eligible faculty for Ph.D. scholarships under the HAU faculty development program.\nPartner with DOST for graduate fellowship grants.", 'Monitoring'],
            ['BSIT', 'Rapid obsolescence of laboratory server hardware and networking equipment affecting instructional quality.', 'High', 'Medium', "Enforce an annual hardware refresh cycle in the SOC capital expenditure budget.\nExplore cloud-based lab alternatives (AWS Academy, Azure Lab).", 'Identified'],
            ['BSCYB', 'Cybersecurity program lacks a certified CHED-recognized cyber range facility for hands-on training.', 'Medium', 'High', "Submit proposal for cyber range facility to the Office of the President.\nPartner with DICT for a shared cyber range access agreement.", 'Identified'],

            // SNAMS
            ['BSN', 'Delays in hospital clinical partner contract renewals affecting student nurse rotation hours and PAASCU compliance.', 'Low', 'High', "Establish backup affiliate contracts with government health units in Pampanga.\nMaintain a rolling 2-year renewal schedule for all hospital contracts.", 'Mitigated'],
            ['BSMT', 'Laboratory reagents and consumables procurement delays due to budget constraints risk laboratory class continuity.', 'Medium', 'Medium', "Establish a 3-month reagent buffer stock.\nSecure long-term supply agreements with medical suppliers.", 'Monitoring'],

            // SEA
            ['BSECE', 'PAASCU Level 3 accreditation expiring November 2026 with insufficient time to complete self-survey report.', 'High', 'High', "Accelerate self-survey committee work with weekly progress reviews.\nEngage external PAASCU consultant for self-survey review.", 'Monitoring'],
            ['BSCE', 'Faculty teaching structural engineering courses lack updated knowledge of the 2023 National Structural Code of the Philippines (NSCP).', 'Low', 'Medium', "Conduct in-house NSCP 2023 training for all structural engineering faculty.\nBudget for faculty attendance at PICE annual convention.", 'Mitigated'],

            // SBA
            ['BSA', 'Declining CPA board examination pass rates below national average in the last two examination cycles.', 'Medium', 'High', "Strengthen pre-board review program with mandatory mock exams.\nRevise curriculum to address identified weak areas from PRC analysis.", 'Monitoring'],
            ['BSBA', 'Low student enrollment in the OJT program due to limited industry partnerships in Clark area.', 'Medium', 'Medium', "Expand industry partner network to Metro Manila and Bataan.\nOffer virtual OJT option for students unable to secure physical placements.", 'Identified'],

            // SED
            ['BEED', 'AUN-QA re-assessment scheduled July 2026 with limited time to address gaps from previous assessment report.', 'High', 'High', "Engage AUN-QA consultant for gap analysis.\nPrioritize Criteria 4 (Programme Structure) and Criteria 7 (Student Support) improvements.", 'Monitoring'],

            // SHTM
            ['BSHM', 'ACPHA facility inspection may reveal non-compliant training kitchen equipment that cannot be replaced within budget cycle.', 'Medium', 'High', "Request emergency capital budget allocation for training kitchen.\nExplore equipment lease-to-own options with hotel equipment suppliers.", 'Identified'],

            // BED
            ['JHS', 'PAASCU Level 3 status may be at risk if the junior high school fails to address faculty qualification gaps before the next site visit.', 'Low', 'Medium', "Enroll JHS teachers in graduate studies through HAU tuition assistance.\nAudit faculty qualifications against PAASCU criteria annually.", 'Identified'],
        ];

        foreach ($risks as [$progCode, $desc, $likelihood, $impact, $mitigation, $status]) {
            if (!isset($p[$progCode])) continue;
            RiskItem::create([
                'program_id'      => $p[$progCode]->program_id,
                'description'     => $desc,
                'likelihood'      => $likelihood,
                'impact'          => $impact,
                'mitigation_plan' => $mitigation,
                'status'          => $status,
            ]);
        }

        // ── GRADUATE RECORDS ──────────────────────────────────────────────────

        $gradRecords = [
            // SOC
            ['BSCS', '2024-2025', '1st Semester', 52],
            ['BSCS', '2024-2025', '2nd Semester', 68],
            ['BSIT', '2024-2025', '1st Semester', 88],
            ['BSIT', '2024-2025', '2nd Semester', 102],
            ['BSCS', '2023-2024', '1st Semester', 45],
            ['BSCS', '2023-2024', '2nd Semester', 61],
            ['BSIT', '2023-2024', '1st Semester', 79],
            ['BSIT', '2023-2024', '2nd Semester', 95],

            // SBA
            ['BSA',  '2024-2025', '1st Semester', 47],
            ['BSA',  '2024-2025', '2nd Semester', 63],
            ['BSBA', '2024-2025', '1st Semester', 72],
            ['BSBA', '2024-2025', '2nd Semester', 88],

            // SNAMS
            ['BSN',  '2024-2025', '1st Semester', 65],
            ['BSN',  '2024-2025', '2nd Semester', 78],
            ['BSMT', '2024-2025', '1st Semester', 32],
            ['BSMT', '2024-2025', '2nd Semester', 28],

            // SEA
            ['BSCE', '2024-2025', '1st Semester', 41],
            ['BSCE', '2024-2025', '2nd Semester', 55],
            ['BSECE','2024-2025', '1st Semester', 38],
            ['BSECE','2024-2025', '2nd Semester', 44],
            ['BSME', '2024-2025', '1st Semester', 29],
            ['BSME', '2024-2025', '2nd Semester', 36],

            // SED
            ['BEED', '2024-2025', '1st Semester', 55],
            ['BEED', '2024-2025', '2nd Semester', 67],
            ['BSED', '2024-2025', '1st Semester', 48],
            ['BSED', '2024-2025', '2nd Semester', 59],

            // SHTM
            ['BSHM', '2024-2025', '1st Semester', 43],
            ['BSHM', '2024-2025', '2nd Semester', 51],
            ['BSTM', '2024-2025', '1st Semester', 37],
            ['BSTM', '2024-2025', '2nd Semester', 42],

            // SAS
            ['BAC',    '2024-2025', '1st Semester', 34],
            ['BAC',    '2024-2025', '2nd Semester', 40],
            ['BSPSYCH','2024-2025', '1st Semester', 29],
            ['BSPSYCH','2024-2025', '2nd Semester', 33],

            // CCJEF
            ['BSCRIM', '2024-2025', '1st Semester', 58],
            ['BSCRIM', '2024-2025', '2nd Semester', 72],
        ];

        foreach ($gradRecords as [$progCode, $sy, $term, $count]) {
            if (!isset($p[$progCode])) continue;
            GraduateRecord::create([
                'program_id'      => $p[$progCode]->program_id,
                'school_year'     => $sy,
                'term'            => $term,
                'graduates_count' => $count,
            ]);
        }

        $this->command->info('✅ Mock data seeded: 0 compliance records, ' . count($risks) . ' risk items, ' . count($gradRecords) . ' graduate records.');
    }

    /**
     * Helper: attach checklist items to a compliance record.
     */
    private function addItems(int $recordId, array $items): void
    {
        foreach ($items as [$text, $completed]) {
            RecommendationItem::create([
                'compliance_record_id' => $recordId,
                'text'                 => $text,
                'is_completed'         => $completed,
                'completed_at'         => $completed ? now()->subDays(rand(5, 60)) : null,
            ]);
        }
    }
}
