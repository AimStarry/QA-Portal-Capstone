<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\AccreditingBody;
use App\Models\College;
use App\Models\ComplianceAssignment;
use App\Models\ComplianceRecord;
use App\Models\GraduateRecord;
use App\Models\Program;
use App\Models\RecommendationItem;
use App\Models\ResponsibleUnit;
use App\Models\RiskItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MockDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Accrediting Bodies ──────────────────────────────────────────
        $bodies = [
            [
                'name' => 'Philippine Accrediting Association of Schools, Colleges and Universities',
                'code' => 'PAASCU',
                'type' => 'Local',
                'description' => 'Private, voluntary non-profit accrediting agency in the Philippines.',
                'areas' => json_encode([
                    'Area I: Philosophy and Objectives',
                    'Area II: Faculty',
                    'Area III: Instruction',
                    'Area IV: Library',
                    'Area V: Laboratories',
                    'Area VI: Physical Plant and Facilities',
                    'Area VII: Student Services',
                    'Area VIII: Administration',
                    'Area IX: Community Extension',
                ]),
            ],
            [
                'name' => 'Philippine Association of Colleges and Universities Commission on Accreditation',
                'code' => 'PACUCOA',
                'type' => 'Local',
                'description' => 'Private accrediting agency providing institutional and program accreditation.',
                'areas' => json_encode([
                    'Area I: Philosophy and Objectives',
                    'Area II: Faculty',
                    'Area III: Instruction',
                    'Area IV: Library',
                    'Area V: Laboratories',
                    'Area VI: Physical Plant',
                    'Area VII: Student Personnel Services',
                    'Area VIII: Social Orientation and Community Involvement',
                    'Area IX: Organization and Administration',
                    'Area X: Research',
                ]),
            ],
            [
                'name' => 'Commission on Higher Education',
                'code' => 'CHED',
                'type' => 'Regulatory',
                'description' => 'Government regulatory body overseeing higher education programs and standards (COE/COD/ISA).',
                'areas' => json_encode([
                    'Criterion 1: Instructional Quality',
                    'Criterion 2: Research and Publications',
                    'Criterion 3: Extension and Linkages',
                    'Criterion 4: Institutional Qualifications',
                ]),
            ],
            [
                'name' => 'Philippine Technological Council',
                'code' => 'PTC',
                'type' => 'Specialized',
                'description' => 'Engineering accreditation body aligning with the Washington Accord.',
                'areas' => json_encode([
                    'Criterion 1: Program Educational Objectives',
                    'Criterion 2: Student Outcomes',
                    'Criterion 3: Students',
                    'Criterion 4: Faculty and Support Staff',
                    'Criterion 5: Curriculum',
                    'Criterion 6: Facilities and Learning Environment',
                    'Criterion 7: Support and Financial Resources',
                    'Criterion 8: Continuous Quality Improvement',
                ]),
            ],
            [
                'name' => 'ASEAN University Network-Quality Assurance',
                'code' => 'AUN-QA',
                'type' => 'International',
                'description' => 'Regional quality assurance network for Southeast Asian higher education institutions.',
                'areas' => json_encode([
                    'Criterion 1: Expected Learning Outcomes',
                    'Criterion 2: Programme Structure and Content',
                    'Criterion 3: Teaching and Learning Approach',
                    'Criterion 4: Student Assessment',
                    'Criterion 5: Academic Staff',
                    'Criterion 6: Student Support Services',
                    'Criterion 7: Facilities and Infrastructure',
                    'Criterion 8: Output and Outcomes',
                ]),
            ],
        ];

        foreach ($bodies as $b) {
            AccreditingBody::updateOrCreate(['code' => $b['code']], $b);
        }

        // ── 2. Colleges ────────────────────────────────────────────────────
        $collegesData = [
            ['name' => 'School of Computing', 'code' => 'SOC', 'former_name' => 'College of Information Technology'],
            ['name' => 'School of Engineering and Architecture', 'code' => 'SEA', 'former_name' => null],
            ['name' => 'School of Business and Accountancy', 'code' => 'SBA', 'former_name' => null],
            ['name' => 'School of Nursing and Allied Medical Sciences', 'code' => 'SNAMS', 'former_name' => 'College of Nursing'],
            ['name' => 'School of Arts and Sciences', 'code' => 'SAS', 'former_name' => null],
            ['name' => 'School of Education', 'code' => 'SED', 'former_name' => null],
            ['name' => 'School of Hospitality and Tourism Management', 'code' => 'SHTM', 'former_name' => null],
            ['name' => 'College of Criminal Justice Education and Forensic Sciences', 'code' => 'CCJEF', 'former_name' => null],
            ['name' => 'Basic Education Department', 'code' => 'BED', 'former_name' => null],
        ];

        $colleges = [];
        foreach ($collegesData as $cd) {
            $colleges[$cd['code']] = College::updateOrCreate(['code' => $cd['code']], $cd);
        }

        // ── 3. Administrative / Support Units ──────────────────────────────
        $unitsData = [
            ['name' => 'Campus Planning Office', 'code' => 'CPO', 'description' => 'Oversees physical plant, classrooms, and architectural developments.'],
            ['name' => 'Office of Student Affairs', 'code' => 'OSA', 'description' => 'Coordinates student welfare, discipline, and student organizations.'],
            ['name' => 'Human Resources Development Office', 'code' => 'HRDO', 'description' => 'Manages faculty ranking, recruitment, and staff development.'],
            ['name' => 'University Library Services', 'code' => 'LIB', 'description' => 'Maintains print and electronic collections, journals, and learning resource spaces.'],
            ['name' => 'Information Technology Services Office', 'code' => 'ITSO', 'description' => 'Maintains campus network, learning management systems, and lab hardware.'],
            ['name' => 'Quality Assurance Office', 'code' => 'QAO', 'description' => 'Directs institutional quality management, mock surveys, and compliance monitoring.'],
        ];

        $units = [];
        foreach ($unitsData as $ud) {
            $units[$ud['code']] = Unit::updateOrCreate(['code' => $ud['code']], $ud);
        }

        // ── 4. Academic Programs ───────────────────────────────────────────
        $programsData = [
            // SOC
            ['college' => 'SOC', 'code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science', 'level' => 'Undergraduate', 'dept' => 'Computer Science'],
            ['college' => 'SOC', 'code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology', 'level' => 'Undergraduate', 'dept' => 'Information Technology'],
            ['college' => 'SOC', 'code' => 'BSCYB', 'name' => 'Bachelor of Science in Cybersecurity', 'level' => 'Undergraduate', 'dept' => 'Cybersecurity'],
            ['college' => 'SOC', 'code' => 'MSIT', 'name' => 'Master of Science in Information Technology', 'level' => "Master's", 'dept' => 'Graduate School of Computing'],

            // SEA
            ['college' => 'SEA', 'code' => 'BSCE', 'name' => 'Bachelor of Science in Civil Engineering', 'level' => 'Undergraduate', 'dept' => 'Civil Engineering'],
            ['college' => 'SEA', 'code' => 'BSECE', 'name' => 'Bachelor of Science in Electronics Engineering', 'level' => 'Undergraduate', 'dept' => 'Electronics Engineering'],
            ['college' => 'SEA', 'code' => 'BSME', 'name' => 'Bachelor of Science in Mechanical Engineering', 'level' => 'Undergraduate', 'dept' => 'Mechanical Engineering'],
            ['college' => 'SEA', 'code' => 'BSARCH', 'name' => 'Bachelor of Science in Architecture', 'level' => 'Undergraduate', 'dept' => 'Architecture'],

            // SBA
            ['college' => 'SBA', 'code' => 'BSA', 'name' => 'Bachelor of Science in Accountancy', 'level' => 'Undergraduate', 'dept' => 'Accountancy'],
            ['college' => 'SBA', 'code' => 'BSBA', 'name' => 'Bachelor of Science in Business Administration', 'level' => 'Undergraduate', 'dept' => 'Business Administration'],
            ['college' => 'SBA', 'code' => 'MBA', 'name' => 'Master in Business Administration', 'level' => "Master's", 'dept' => 'Graduate School of Business'],

            // SNAMS
            ['college' => 'SNAMS', 'code' => 'BSN', 'name' => 'Bachelor of Science in Nursing', 'level' => 'Undergraduate', 'dept' => 'Nursing'],
            ['college' => 'SNAMS', 'code' => 'BSMT', 'name' => 'Bachelor of Science in Medical Technology', 'level' => 'Undergraduate', 'dept' => 'Medical Technology'],
            ['college' => 'SNAMS', 'code' => 'MAN', 'name' => 'Master of Arts in Nursing', 'level' => "Master's", 'dept' => 'Graduate School of Nursing'],

            // SAS
            ['college' => 'SAS', 'code' => 'BAC', 'name' => 'Bachelor of Arts in Communication', 'level' => 'Undergraduate', 'dept' => 'Communication'],
            ['college' => 'SAS', 'code' => 'BSPSYCH', 'name' => 'Bachelor of Science in Psychology', 'level' => 'Undergraduate', 'dept' => 'Psychology'],

            // SED
            ['college' => 'SED', 'code' => 'BEED', 'name' => 'Bachelor of Elementary Education', 'level' => 'Undergraduate', 'dept' => 'Teacher Education'],
            ['college' => 'SED', 'code' => 'BSED', 'name' => 'Bachelor of Secondary Education', 'level' => 'Undergraduate', 'dept' => 'Teacher Education'],

            // SHTM
            ['college' => 'SHTM', 'code' => 'BSHM', 'name' => 'Bachelor of Science in Hospitality Management', 'level' => 'Undergraduate', 'dept' => 'Hospitality Management'],
            ['college' => 'SHTM', 'code' => 'BSTM', 'name' => 'Bachelor of Science in Tourism Management', 'level' => 'Undergraduate', 'dept' => 'Tourism Management'],

            // CCJEF
            ['college' => 'CCJEF', 'code' => 'BSCRIM', 'name' => 'Bachelor of Science in Criminology', 'level' => 'Undergraduate', 'dept' => 'Criminology'],

            // BED
            ['college' => 'BED', 'code' => 'SHS', 'name' => 'Senior High School Program', 'level' => 'K-12', 'dept' => 'Senior High School'],
            ['college' => 'BED', 'code' => 'JHS', 'name' => 'Junior High School Program', 'level' => 'K-12', 'dept' => 'Junior High School'],
        ];

        $programs = [];
        foreach ($programsData as $pd) {
            $college = $colleges[$pd['college']] ?? null;
            if (!$college) continue;

            $programs[$pd['code']] = Program::updateOrCreate(
                ['program_code' => $pd['code']],
                [
                    'college_id'   => $college->college_id,
                    'program_name' => $pd['name'],
                    'level'        => $pd['level'],
                    'department'   => $pd['dept'],
                ]
            );
        }

        // ── 5. Mock Accreditations ─────────────────────────────────────────
        $accreditationsData = [
            // SOC
            ['code' => 'BSCS', 'body' => 'PAASCU', 'level' => 'Level III Re-accredited', 'survey' => '2023-11-15', 'valid_from' => '2023-12-01', 'valid_to' => '2028-11-30', 'status' => 'Accredited'],
            ['code' => 'BSIT', 'body' => 'PAASCU', 'level' => 'Level III Re-accredited', 'survey' => '2023-11-15', 'valid_from' => '2023-12-01', 'valid_to' => '2028-11-30', 'status' => 'Accredited'],
            ['code' => 'BSCS', 'body' => 'CHED',   'level' => 'Center of Development (COD)', 'survey' => '2024-03-10', 'valid_from' => '2024-04-01', 'valid_to' => '2027-03-31', 'status' => 'Accredited'],
            ['code' => 'BSCYB', 'body' => 'PAASCU', 'level' => 'Candidate Status', 'survey' => '2025-02-20', 'valid_from' => '2025-03-01', 'valid_to' => '2027-02-28', 'status' => 'Candidate'],

            // SEA
            ['code' => 'BSCE', 'body' => 'PTC',    'level' => 'Full Accreditation (Tier 1)', 'survey' => '2022-10-10', 'valid_from' => '2022-11-01', 'valid_to' => '2027-10-31', 'status' => 'Accredited'],
            ['code' => 'BSECE', 'body' => 'PTC',   'level' => 'Full Accreditation (Tier 1)', 'survey' => '2022-10-10', 'valid_from' => '2022-11-01', 'valid_to' => '2027-10-31', 'status' => 'Accredited'],
            ['code' => 'BSME', 'body' => 'PAASCU', 'level' => 'Level II Re-accredited', 'survey' => '2024-01-18', 'valid_from' => '2024-02-01', 'valid_to' => '2029-01-31', 'status' => 'Accredited'],

            // SBA
            ['code' => 'BSA',  'body' => 'PAASCU', 'level' => 'Level IV Accredited', 'survey' => '2021-09-14', 'valid_from' => '2021-10-01', 'valid_to' => '2026-09-30', 'status' => 'Expiring Soon'],
            ['code' => 'BSBA', 'body' => 'PAASCU', 'level' => 'Level III Re-accredited', 'survey' => '2023-05-12', 'valid_from' => '2023-06-01', 'valid_to' => '2028-05-31', 'status' => 'Accredited'],

            // SNAMS
            ['code' => 'BSN',  'body' => 'PAASCU', 'level' => 'Level III Re-accredited', 'survey' => '2022-04-10', 'valid_from' => '2022-05-01', 'valid_to' => '2027-04-30', 'status' => 'Accredited'],
            ['code' => 'BSMT', 'body' => 'PACUCOA', 'level' => 'Level II Accredited', 'survey' => '2023-08-22', 'valid_from' => '2023-09-01', 'valid_to' => '2026-08-31', 'status' => 'Expiring Soon'],

            // SAS & SED
            ['code' => 'BSPSYCH', 'body' => 'PAASCU', 'level' => 'Level II Re-accredited', 'survey' => '2024-06-15', 'valid_from' => '2024-07-01', 'valid_to' => '2029-06-30', 'status' => 'Accredited'],
            ['code' => 'BEED', 'body' => 'AUN-QA', 'level' => 'AUN-QA Certified', 'survey' => '2023-09-05', 'valid_from' => '2023-10-01', 'valid_to' => '2028-09-30', 'status' => 'Accredited'],

            // BED
            ['code' => 'JHS',  'body' => 'PAASCU', 'level' => 'Level III Re-accredited', 'survey' => '2022-11-20', 'valid_from' => '2022-12-01', 'valid_to' => '2027-11-30', 'status' => 'Accredited'],
            ['code' => 'SHS',  'body' => 'PAASCU', 'level' => 'Candidate Status', 'survey' => '2024-11-10', 'valid_from' => '2024-12-01', 'valid_to' => '2026-11-30', 'status' => 'Candidate'],
        ];

        foreach ($accreditationsData as $ad) {
            $prog = $programs[$ad['code']] ?? null;
            if (!$prog) continue;

            Accreditation::updateOrCreate(
                [
                    'program_id'       => $prog->program_id,
                    'accrediting_body' => $ad['body'],
                ],
                [
                    'accreditation_level' => $ad['level'],
                    'survey_date'         => $ad['survey'],
                    'valid_from'          => $ad['valid_from'],
                    'valid_to'            => $ad['valid_to'],
                    'status'              => $ad['status'],
                    'decision_date'       => $ad['valid_from'],
                ]
            );
        }

        // ── 6. Mock Users ──────────────────────────────────────────────────
        $usersData = [
            [
                'username'   => 'admin',
                'name'       => 'System Administrator',
                'first_name' => 'System',
                'last_name'  => 'Administrator',
                'email'      => 'admin@example.edu',
                'usertype'   => 'QA Admin',
                'college_id' => null,
                'unit_id'    => null,
            ],
            [
                'username'   => 'qaoadmin',
                'name'       => 'QA Officer Lead',
                'first_name' => 'QA',
                'last_name'  => 'Officer',
                'email'      => 'qao@example.edu',
                'usertype'   => 'QA Admin',
                'college_id' => null,
                'unit_id'    => $units['QAO']->unit_id ?? null,
            ],
            [
                'username'   => 'dean_soc',
                'name'       => 'Dean School of Computing',
                'first_name' => 'Dean',
                'last_name'  => 'Computing',
                'email'      => 'dean.soc@example.edu',
                'usertype'   => 'Dean',
                'college_id' => $colleges['SOC']->college_id ?? null,
                'unit_id'    => null,
            ],
            [
                'username'   => 'dean_sea',
                'name'       => 'Dean School of Engineering',
                'first_name' => 'Dean',
                'last_name'  => 'Engineering',
                'email'      => 'dean.sea@example.edu',
                'usertype'   => 'Dean',
                'college_id' => $colleges['SEA']->college_id ?? null,
                'unit_id'    => null,
            ],
            [
                'username'   => 'dean_snams',
                'name'       => 'Dean Nursing & Allied Medical',
                'first_name' => 'Dean',
                'last_name'  => 'Nursing',
                'email'      => 'dean.snams@example.edu',
                'usertype'   => 'Dean',
                'college_id' => $colleges['SNAMS']->college_id ?? null,
                'unit_id'    => null,
            ],
            [
                'username'   => 'dean_sba',
                'name'       => 'Dean Business & Accountancy',
                'first_name' => 'Dean',
                'last_name'  => 'Business',
                'email'      => 'dean.sba@example.edu',
                'usertype'   => 'Dean',
                'college_id' => $colleges['SBA']->college_id ?? null,
                'unit_id'    => null,
            ],
            [
                'username'   => 'unit_cpo',
                'name'       => 'Head Campus Planning',
                'first_name' => 'Campus',
                'last_name'  => 'Planning',
                'email'      => 'cpo@example.edu',
                'usertype'   => 'Staff',
                'college_id' => null,
                'unit_id'    => $units['CPO']->unit_id ?? null,
            ],
            [
                'username'   => 'unit_itso',
                'name'       => 'Head IT Services',
                'first_name' => 'IT',
                'last_name'  => 'Services',
                'email'      => 'itso@example.edu',
                'usertype'   => 'Staff',
                'college_id' => null,
                'unit_id'    => $units['ITSO']->unit_id ?? null,
            ],
        ];

        foreach ($usersData as $ud) {
            User::updateOrCreate(
                ['username' => $ud['username']],
                array_merge($ud, [
                    'password' => Hash::make('password'),
                ])
            );
        }

        // ── 7. Mock Compliance Records & Recommendations ───────────────────
        $complianceRecordsData = [
            [
                'program_code'     => 'BSCS',
                'title'            => 'Upgrade High-Performance Computing and AI Laboratory Infrastructure',
                'accrediting_body' => 'PAASCU',
                'school'           => 'School of Computing',
                'area'             => 'Area V: Laboratories',
                'category'         => 'Laboratories',
                'priority'         => 'High',
                'status'           => 'In Progress',
                'due_date'         => Carbon::now()->addMonths(3)->format('Y-m-d'),
                'visit_date'       => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'responsible_unit' => 'School of Computing',
                'description'      => 'Acquire dedicated GPU workstations for deep learning and data engineering capstone projects as cited during PAASCU survey visit.',
                'action_plan'      => 'Procure 20 RTX 4080 GPU workstations and configure isolated containerized runtime environments for student capstone experiments.',
                'evidence_links'   => 'https://example.edu/evidence/soc-hpc-procurement.pdf',
                'items'            => [
                    ['Prepare technical specifications and capital expenditure proposal', true],
                    ['Obtain budget approval from Institutional Planning & Finance Committee', true],
                    ['Vendor bidding and delivery of GPU servers', false],
                    ['Lab commissioning, networking, and safety validation', false],
                ],
            ],
            [
                'program_code'     => 'BSN',
                'title'            => 'Formalize Affiliate Hospital Clinical Rotation MOAs with Regional Medical Centers',
                'accrediting_body' => 'PAASCU',
                'school'           => 'School of Nursing and Allied Medical Sciences',
                'area'             => 'Area III: Instruction',
                'category'         => 'Curriculum / Instruction',
                'priority'         => 'Critical',
                'status'           => 'Compliant',
                'due_date'         => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'visit_date'       => Carbon::now()->subMonths(8)->format('Y-m-d'),
                'responsible_unit' => 'School of Nursing and Allied Medical Sciences',
                'description'      => 'Ensure full clinical affiliation contracts are in active status with Level 3 tertiary hospitals in the region for nursing student duties.',
                'action_plan'      => 'Review legal contracts, secure endorsements from the University Legal Counsel, and sign bilateral MOA agreements.',
                'evidence_links'   => 'https://example.edu/evidence/snams-hospital-moa-signed.pdf',
                'items'            => [
                    ['Draft standard clinical training agreement with tertiary hospital affiliates', true],
                    ['Secure DOH and legal compliance endorsement', true],
                    ['Bilateral contract signing and faculty preceptor orientation', true],
                ],
            ],
            [
                'program_code'     => 'BSCE',
                'title'            => 'Calibrate Materials Testing and Hydraulics Laboratory Apparatuses',
                'accrediting_body' => 'PTC',
                'school'           => 'School of Engineering and Architecture',
                'area'             => 'Criterion 6: Facilities and Learning Environment',
                'category'         => 'Laboratories',
                'priority'         => 'Medium',
                'status'           => 'For Review',
                'due_date'         => Carbon::now()->addMonths(1)->format('Y-m-d'),
                'visit_date'       => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'responsible_unit' => 'School of Engineering and Architecture',
                'description'      => 'All Universal Testing Machines (UTM) and hydraulic flumes require annual ISO calibration certificates for PTC compliance.',
                'action_plan'      => 'Engage DOST-accredited calibration laboratory for on-site inspection and tagging of civil engineering testing rigs.',
                'evidence_links'   => 'https://example.edu/evidence/sea-calibration-certs.pdf',
                'items'            => [
                    ['Inventory all civil engineering mechanical test instruments', true],
                    ['Schedule on-site calibration with accredited technical team', true],
                    ['Attach calibration stickers and archive test certificates in QAO portal', false],
                ],
            ],
            [
                'program_code'     => 'BSA',
                'title'            => 'Curricular Integration of Advanced Data Analytics in Accounting Systems',
                'accrediting_body' => 'PAASCU',
                'school'           => 'School of Business and Accountancy',
                'area'             => 'Area III: Instruction',
                'category'         => 'Curriculum / Instruction',
                'priority'         => 'High',
                'status'           => 'In Progress',
                'due_date'         => Carbon::now()->addMonths(2)->format('Y-m-d'),
                'visit_date'       => Carbon::now()->subMonths(10)->format('Y-m-d'),
                'responsible_unit' => 'School of Business and Accountancy',
                'description'      => 'Update syllabi for Accounting Information Systems to incorporate automated audit analytics, Power BI, and Python libraries.',
                'action_plan'      => 'Conduct faculty training workshop on financial data visualization and update course specifications for CHED & PAASCU audit.',
                'evidence_links'   => 'https://example.edu/evidence/sba-curriculum-revision-2026.pdf',
                'items'            => [
                    ['Benchmark revised accounting analytics curriculum with industry advisory board', true],
                    ['Organize 40-hour hands-on faculty upskilling boot camp', true],
                    ['Submit revised syllabi to Curriculum and Standards Committee for sign-off', false],
                ],
            ],
            [
                'program_code'     => null, // Institutional / Multi-school
                'title'            => 'Fire Safety, Emergency Evacuation Routes, and PWD Ramp Audit',
                'accrediting_body' => 'PAASCU',
                'school'           => 'Campus Planning Office',
                'area'             => 'Area VI: Physical Plant and Facilities',
                'category'         => 'Facilities',
                'priority'         => 'Critical',
                'status'           => 'Compliant',
                'due_date'         => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'visit_date'       => Carbon::now()->subMonths(12)->format('Y-m-d'),
                'responsible_unit' => 'Campus Planning Office',
                'description'      => 'Comprehensive accessibility audit across academic halls in compliance with Batas Pambansa 344 and PAASCU standards.',
                'action_plan'      => 'Retrofit handrails, repaint tactile floor markers, install emergency exit illumination, and audit fire hose stations.',
                'evidence_links'   => 'https://example.edu/evidence/cpo-fire-pwd-audit-report.pdf',
                'items'            => [
                    ['Campus-wide physical inspection with Bureau of Fire Protection', true],
                    ['Install tactile pavers and automatic door pushbars on main entryways', true],
                    ['Publish interactive evacuation maps in all building foyers', true],
                ],
            ],
        ];

        foreach ($complianceRecordsData as $crd) {
            $prog = $crd['program_code'] ? ($programs[$crd['program_code']] ?? null) : null;
            $items = $crd['items'];
            unset($crd['items'], $crd['program_code']);

            $record = ComplianceRecord::create(array_merge($crd, [
                'program_id' => $prog ? $prog->program_id : null,
            ]));

            // Add checklist items
            foreach ($items as [$itemText, $isCompleted]) {
                RecommendationItem::create([
                    'compliance_record_id' => $record->compliance_record_id,
                    'text'                 => $itemText,
                    'is_completed'         => $isCompleted,
                    'completed_at'         => $isCompleted ? Carbon::now()->subDays(rand(5, 30)) : null,
                ]);
            }

            // Create assignment
            ComplianceAssignment::create([
                'compliance_record_id' => $record->compliance_record_id,
                'program_id'           => $prog ? $prog->program_id : null,
                'school_name'          => $record->school,
            ]);
        }

        // ── 8. Mock Risk Items ─────────────────────────────────────────────
        $risksData = [
            ['BSCS', 'Insufficient faculty members holding earned doctorate degrees in computer science affecting PAASCU Level IV accreditation benchmark.', 'High', 'High', 'Sponsor full faculty doctoral scholarships and create research release time incentives.', 'Monitoring'],
            ['BSIT', 'Rapid hardware lifecycle obsolescence in networking and cloud virtualization labs.', 'Medium', 'Medium', 'Adopt hybrid cloud virtualization subscriptions (AWS / Azure) and annual hardware replacement budget.', 'Identified'],
            ['BSN', 'Changes in PRC Nursing Licensure Exam guidelines requiring increased clinical simulation laboratory hours.', 'Low', 'High', 'Procure high-fidelity patient mannequins and expand simulation center shift capacity.', 'Mitigated'],
            ['BSA', 'Fluctuating CPA board examination pass rates below university strategic target.', 'Medium', 'High', 'Mandate diagnostic pre-board mock examinations and integrate specialized review modules.', 'Monitoring'],
            ['BSCE', 'Accreditation expiry approaching within 18 months requiring completion of Self-Survey Report (SSR).', 'High', 'High', 'Form specialized survey committees per area and conduct internal mock evaluation.', 'Monitoring'],
            ['SHS', 'Classroom space constraints due to surging senior high school track enrollment.', 'Medium', 'Medium', 'Optimize room scheduling algorithms and allocate newly constructed academic wing.', 'Mitigated'],
        ];

        foreach ($risksData as [$progCode, $desc, $likelihood, $impact, $mitigation, $status]) {
            $prog = $programs[$progCode] ?? null;
            if (!$prog) continue;

            RiskItem::create([
                'program_id'      => $prog->program_id,
                'description'     => $desc,
                'likelihood'      => $likelihood,
                'impact'          => $impact,
                'mitigation_plan' => $mitigation,
                'status'          => $status,
            ]);
        }

        // ── 9. Mock Graduate Records ───────────────────────────────────────
        $graduatesData = [
            ['BSCS', '2023-2024', '1st Semester', 42],
            ['BSCS', '2023-2024', '2nd Semester', 58],
            ['BSCS', '2024-2025', '1st Semester', 48],
            ['BSCS', '2024-2025', '2nd Semester', 65],
            ['BSIT', '2023-2024', '1st Semester', 75],
            ['BSIT', '2023-2024', '2nd Semester', 92],
            ['BSIT', '2024-2025', '1st Semester', 84],
            ['BSIT', '2024-2025', '2nd Semester', 106],
            ['BSA',  '2024-2025', '1st Semester', 45],
            ['BSA',  '2024-2025', '2nd Semester', 60],
            ['BSN',  '2024-2025', '1st Semester', 62],
            ['BSN',  '2024-2025', '2nd Semester', 76],
            ['BSCE', '2024-2025', '1st Semester', 39],
            ['BSCE', '2024-2025', '2nd Semester', 52],
        ];

        foreach ($graduatesData as [$progCode, $sy, $term, $count]) {
            $prog = $programs[$progCode] ?? null;
            if (!$prog) continue;

            GraduateRecord::create([
                'program_id'      => $prog->program_id,
                'school_year'     => $sy,
                'term'            => $term,
                'graduates_count' => $count,
            ]);
        }
    }
}
