<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\RiskItem;
use App\Models\GraduateRecord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        // Seed HAU Programs
        $bscs = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'college' => 'School of Computing (SOC)',
            'program_level' => 'Undergraduate',
        ]);

        $bsit = Program::create([
            'program_code' => 'BSIT',
            'program_name' => 'Bachelor of Science in Information Technology',
            'college' => 'School of Computing (SOC)',
            'program_level' => 'Undergraduate',
        ]);

        $bsis = Program::create([
            'program_code' => 'BSIS',
            'program_name' => 'Bachelor of Science in Information Systems',
            'college' => 'School of Computing (SOC)',
            'program_level' => 'Undergraduate',
        ]);

        $bsce = Program::create([
            'program_code' => 'BSCE',
            'program_name' => 'Bachelor of Science in Civil Engineering',
            'college' => 'School of Engineering and Architecture (SEA)',
            'program_level' => 'Undergraduate',
        ]);

        $bsa = Program::create([
            'program_code' => 'BSA',
            'program_name' => 'Bachelor of Science in Accountancy',
            'college' => 'School of Business and Accountancy (SBA)',
            'program_level' => 'Undergraduate',
        ]);

        $bsn = Program::create([
            'program_code' => 'BSN',
            'program_name' => 'Bachelor of Science in Nursing',
            'college' => 'School of Nursing and Allied Health Sciences (SNAHS)',
            'program_level' => 'Undergraduate',
        ]);

        $bsba = Program::create([
            'program_code' => 'BSBA',
            'program_name' => 'Bachelor of Science in Business Administration',
            'college' => 'School of Business and Accountancy (SBA)',
            'program_level' => 'Undergraduate',
        ]);

        $bshm = Program::create([
            'program_code' => 'BSHM',
            'program_name' => 'Bachelor of Science in Hospitality Management',
            'college' => 'School of Hospitality and Tourism Management (SHTM)',
            'program_level' => 'Undergraduate',
        ]);

        $mscs = Program::create([
            'program_code' => 'MSCS',
            'program_name' => 'Master of Science in Computer Science',
            'college' => 'School of Computing (SOC)',
            'program_level' => "Master's",
        ]);

        $mba = Program::create([
            'program_code' => 'MBA',
            'program_name' => 'Master in Business Administration',
            'college' => 'School of Business and Accountancy (SBA)',
            'program_level' => "Master's",
        ]);

        // Seed Accreditations (Accrediting Body)
        // 1. BSCS - PAASCU Level III Active
        Accreditation::create([
            'program_id' => $bscs->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level III Re-accredited',
            'last_visit' => '2024-02-15',
            'expiry_date' => '2029-02-15',
            'status' => 'Active',
        ]);

        // 2. BSIT - PAASCU Level II Expiring Soon
        Accreditation::create([
            'program_id' => $bsit->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level II Re-accredited',
            'last_visit' => '2021-11-10',
            'expiry_date' => '2026-11-10',
            'status' => 'Expiring Soon',
        ]);

        // 3. BSIS - PAASCU Candidate Pending
        Accreditation::create([
            'program_id' => $bsis->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Candidate Status',
            'last_visit' => '2025-06-01',
            'expiry_date' => null,
            'status' => 'Pending',
        ]);

        // 4. BSCE - PACUCOA Level III Active
        Accreditation::create([
            'program_id' => $bsce->id,
            'accrediting_body' => 'PACUCOA',
            'type' => 'Local',
            'level_or_tier' => 'Level III Accredited',
            'last_visit' => '2023-05-20',
            'expiry_date' => '2028-05-20',
            'status' => 'Active',
        ]);

        // 5. BSA - PAASCU Level IV Active
        Accreditation::create([
            'program_id' => $bsa->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level IV Re-accredited',
            'last_visit' => '2022-10-18',
            'expiry_date' => '2027-10-18',
            'status' => 'Active',
        ]);

        // 6. BSN - PAASCU Level II Expired
        Accreditation::create([
            'program_id' => $bsn->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level II Re-accredited',
            'last_visit' => '2020-03-05',
            'expiry_date' => '2025-03-05',
            'status' => 'Expired',
        ]);

        // 7. BSBA - PAASCU Level III Active
        Accreditation::create([
            'program_id' => $bsba->id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level III Re-accredited',
            'last_visit' => '2023-11-12',
            'expiry_date' => '2028-11-12',
            'status' => 'Active',
        ]);

        // 8. BSHM - PACUCOA Level I Active
        Accreditation::create([
            'program_id' => $bshm->id,
            'accrediting_body' => 'PACUCOA',
            'type' => 'Local',
            'level_or_tier' => 'Level I Accredited',
            'last_visit' => '2024-09-15',
            'expiry_date' => '2027-09-15',
            'status' => 'Active',
        ]);

        // ================= SEED COMPLIANCE RECORDS WITH DOCUMENT LINKS & APPROVALS =================
        
        // 1. Approved / Compliant record (BSCS)
        ComplianceRecord::create([
            'program_id' => $bscs->id,
            'title' => 'Submit Updated Syllabi for CS Professional Electives',
            'description' => 'Ensure all professional elective course syllabi contain updated research and industry alignment.',
            'status' => 'Compliant',
            'due_date' => '2026-05-10',
            'responsible_unit' => 'SOC - Computer Science Department',
            'document_link' => 'https://drive.google.com/file/d/1bscs_syllabi_professional_electives/view',
            'approval_state' => 'None',
        ]);

        // 2. Draft / Propose Update awaiting approval (BSCS)
        ComplianceRecord::create([
            'program_id' => $bscs->id,
            'title' => 'Conduct Alumni Tracer and Employment Survey',
            'description' => 'Gather employment data from batches 2022 to 2025 for graduate profile evaluation.',
            'status' => 'Pending',
            'due_date' => '2026-08-15',
            'responsible_unit' => 'SOC - CS Alumni Committee',
            'document_link' => null,
            'pending_status' => 'Compliant',
            'pending_document_link' => 'https://docs.google.com/spreadsheets/d/1bscs_alumni_tracer_2026/edit',
            'approval_state' => 'Pending Approval',
        ]);

        // 3. Approved / Compliant record (BSIT)
        ComplianceRecord::create([
            'program_id' => $bsit->id,
            'title' => 'Industry Advisory Board (IAB) Meeting Minutes',
            'description' => 'Document consultation minutes with IT board members regarding curriculum revisions.',
            'status' => 'Compliant',
            'due_date' => '2026-04-20',
            'responsible_unit' => 'SOC - IT Industry Liaison',
            'document_link' => 'https://drive.google.com/file/d/1bsit_iab_meeting_minutes_2025/view',
            'approval_state' => 'None',
        ]);

        // 4. Propose Update awaiting approval on a Non-Compliant record (BSIT)
        ComplianceRecord::create([
            'program_id' => $bsit->id,
            'title' => 'Submit Host Training Establishment (HTE) Internship MOAs',
            'description' => 'Compile student internship certificates and partner company MOA documents.',
            'status' => 'Non-Compliant',
            'due_date' => '2026-06-10',
            'responsible_unit' => 'SOC - Practicum Coordinator',
            'document_link' => null,
            'pending_status' => 'Compliant',
            'pending_document_link' => 'https://drive.google.com/file/d/1bsit_hte_internship_moas/view',
            'approval_state' => 'Pending Approval',
        ]);

        // 5. Approved / Compliant record (BSA)
        ComplianceRecord::create([
            'program_id' => $bsa->id,
            'title' => 'CPALE Board Exam Performance Report',
            'description' => 'Submit official analysis of HAU graduates performance in the recent CPA board examinations.',
            'status' => 'Compliant',
            'due_date' => '2026-03-15',
            'responsible_unit' => 'SBA - Accountancy Department',
            'document_link' => 'https://drive.google.com/file/d/1bsa_cpale_exam_performance_report/view',
            'approval_state' => 'None',
        ]);

        // 6. Propose Update awaiting approval on a Pending record (BSN)
        ComplianceRecord::create([
            'program_id' => $bsn->id,
            'title' => 'Hospital Clinical Affiliation Contracts',
            'description' => 'Renew contracts with partner base hospitals for student nurse clinical rotations.',
            'status' => 'Pending',
            'due_date' => '2026-09-30',
            'responsible_unit' => 'SNAHS - Nursing Clinical Coordinator',
            'document_link' => null,
            'pending_status' => 'Compliant',
            'pending_document_link' => 'https://drive.google.com/file/d/1bsn_clinical_affiliation_draft/view',
            'approval_state' => 'Pending Approval',
        ]);

        // 7. Rejected submission requiring correction (BSIS)
        ComplianceRecord::create([
            'program_id' => $bsis->id,
            'title' => 'Self-Survey QA Review Report',
            'description' => 'Perform self-evaluation check across computing criteria and compile report.',
            'status' => 'Pending',
            'due_date' => '2026-07-20',
            'responsible_unit' => 'SOC - IS QA Committee',
            'document_link' => null,
            'pending_status' => 'Compliant',
            'pending_document_link' => 'https://drive.google.com/file/d/1bsis_self_survey_qa_review_draft/view',
            'approval_state' => 'Rejected',
            'rejection_reason' => 'The attached document is missing signatures from the college committee. Please re-sign and submit.',
        ]);

        // ================= SEED RISK ITEMS =================
        RiskItem::create([
            'program_id' => $bscs->id,
            'description' => 'Shortage of doctorate-degree holding Computer Science full-time faculty members.',
            'likelihood' => 'Medium',
            'impact' => 'High',
            'mitigation_plan' => 'Sponsor faculty for local and international Ph.D. scholarships under HAU development funds.',
            'status' => 'Monitoring',
        ]);

        RiskItem::create([
            'program_id' => $bsit->id,
            'description' => 'Rapid obsolescence of lab server hardware and networking equipment.',
            'likelihood' => 'High',
            'impact' => 'Medium',
            'mitigation_plan' => 'Enforce an automated hardware refresh cycle in the SOC annual capital expenditure budget.',
            'status' => 'Identified',
        ]);

        RiskItem::create([
            'program_id' => $bsn->id,
            'description' => 'Delays in clinical partner hospital renewals affecting student internship hours.',
            'likelihood' => 'Low',
            'impact' => 'High',
            'mitigation_plan' => 'Establish backup affiliate contracts with primary government health units in Pampanga.',
            'status' => 'Mitigated',
        ]);

        // ================= SEED GRADUATE RECORDS =================
        GraduateRecord::create([
            'program_id' => $bscs->id,
            'school_year' => '2024-2025',
            'term' => '1st Semester',
            'graduates_count' => 45,
        ]);
        GraduateRecord::create([
            'program_id' => $bscs->id,
            'school_year' => '2024-2025',
            'term' => '2nd Semester',
            'graduates_count' => 60,
        ]);
        GraduateRecord::create([
            'program_id' => $bsit->id,
            'school_year' => '2024-2025',
            'term' => '1st Semester',
            'graduates_count' => 80,
        ]);
        GraduateRecord::create([
            'program_id' => $bsit->id,
            'school_year' => '2024-2025',
            'term' => '2nd Semester',
            'graduates_count' => 95,
        ]);
        GraduateRecord::create([
            'program_id' => $bsis->id,
            'school_year' => '2024-2025',
            'term' => '1st Trimester',
            'graduates_count' => 15,
        ]);
        GraduateRecord::create([
            'program_id' => $bsis->id,
            'school_year' => '2024-2025',
            'term' => '2nd Trimester',
            'graduates_count' => 20,
        ]);
        GraduateRecord::create([
            'program_id' => $bsis->id,
            'school_year' => '2024-2025',
            'term' => '3rd Trimester',
            'graduates_count' => 18,
        ]);
    }
}
