<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Program;
use App\Models\Accreditation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real HAU colleges, programs, and current accreditation data
 * sourced from the Excel files in the project root.
 *
 * Run:  php artisan db:seed --class=HauRealDataSeeder
 *
 * NOTE: This seeder clears existing colleges, programs, and accreditations
 * before inserting the real values. Compliance records, risk items, and
 * graduate records linked to the old fake programs are also removed.
 */
class HauRealDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Wipe dependent tables first to avoid FK violations ──────────
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
        DB::table('accreditations')->delete();
        DB::table('programs')->delete();
        DB::table('colleges')->delete();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // ── 2. Colleges / Schools ──────────────────────────────────────────
        $colleges = [];

        $colleges['SAS'] = College::create([
            'name'        => 'School of Arts and Sciences',
            'code'        => 'SAS',
            'former_name' => null,
        ]);
        $colleges['SBA'] = College::create([
            'name'        => 'School of Business and Accountancy',
            'code'        => 'SBA',
            'former_name' => null,
        ]);
        $colleges['CCJEF'] = College::create([
            'name'        => 'College of Criminal Justice Education and Forensic Sciences',
            'code'        => 'CCJEF',
            'former_name' => null,
        ]);
        $colleges['SED'] = College::create([
            'name'        => 'School of Education',
            'code'        => 'SED',
            'former_name' => null,
        ]);
        $colleges['SEA'] = College::create([
            'name'        => 'School of Engineering and Architecture',
            'code'        => 'SEA',
            'former_name' => null,
        ]);
        $colleges['SHTM'] = College::create([
            'name'        => 'School of Hospitality and Tourism Management',
            'code'        => 'SHTM',
            'former_name' => null,
        ]);
        $colleges['SNAMS'] = College::create([
            'name'        => 'School of Nursing and Allied Medical Sciences',
            'code'        => 'SNAMS',
            'former_name' => 'College of Nursing (CON)',
        ]);
        $colleges['SOC'] = College::create([
            'name'        => 'School of Computing',
            'code'        => 'SOC',
            'former_name' => 'College of Information and Communications Technology (CICT)',
        ]);
        $colleges['BED'] = College::create([
            'name'        => 'Basic Education',
            'code'        => 'BED',
            'former_name' => null,
        ]);

        // ── 3. Programs ────────────────────────────────────────────────────
        // Format: [ college_code, program_code, program_name, level, department ]
        // Level: 'Undergraduate' | "Master's" | 'Doctoral' | 'K-12'

        $programData = [
            // ── BED (Basic Education) ──────────────────────────────────────
            ['BED', 'LES',  'Laboratory Elementary School',  'K-12',          'Basic Education'],
            ['BED', 'JHS',  'Junior High School',  'K-12',          'Basic Education'],
            ['BED', 'SHS',  'Senior High School',  'K-12',          'Basic Education'],

            // ── SAS (School of Arts and Sciences) ─────────────────────────
            ['SAS', 'BAC',   'Bachelor of Arts in Communication',                         'Undergraduate', 'Arts and Sciences'],
            ['SAS', 'BSPSYCH','Bachelor of Science in Psychology',                         'Undergraduate', 'Arts and Sciences'],
            ['SAS', 'MSPSYCH','Master of Science in Psychology',                           "Master's",      'Graduate School'],
            ['SAS', 'MAC',   'Master of Arts in Communication',                           "Master's",      'Graduate School'],
            ['SAS', 'MAGC',  'Master of Arts in Guidance and Counselling',                "Master's",      'Graduate School'],
            ['SAS', 'MDMC',  'Master in Digital Marketing Communication',                 "Master's",      'Graduate School'],
            ['SAS', 'PHDPSYCH','Doctor of Philosophy in Psychology',                      'Doctoral',      'Graduate School'],

            // ── SBA (School of Business and Accountancy) ───────────────────
            ['SBA', 'BSA',   'Bachelor of Science in Accountancy',                        'Undergraduate', 'Accountancy'],
            ['SBA', 'BSAM',  'Bachelor of Science in Aviation Management',                'Undergraduate', 'Business Administration'],
            ['SBA', 'BSMA',  'Bachelor of Science in Management Accounting',              'Undergraduate', 'Accountancy'],
            ['SBA', 'BSIA',  'Bachelor of Science in Internal Auditing',                  'Undergraduate', 'Accountancy'],
            ['SBA', 'BSBA',  'Bachelor of Science in Business Administration',            'Undergraduate', 'Business Administration'],
            ['SBA', 'GMBA',  'Master of Business Administration in Leadership for a Sustainable Economy (Green MBA)', "Master's", 'Graduate School'],
            ['SBA', 'MBA',   'Master of Business Administration (MBA)',                   "Master's",      'Graduate School'],
            ['SBA', 'MPA',   'Master in Public Administration (MPA)',                     "Master's",      'Graduate School'],
            ['SBA', 'MSA',   'Master of Science in Accountancy (MSA)',                    "Master's",      'Graduate School'],
            ['SBA', 'DBA',   'Doctor of Business Administration (DBA)',                   'Doctoral',      'Graduate School'],

            // ── CCJEF (College of Criminal Justice) ───────────────────────
            ['CCJEF', 'BSCRIM', 'Bachelor of Science in Criminology',                    'Undergraduate', 'Criminology'],
            ['CCJEF', 'BFS',    'Bachelor of Forensic Science',                          'Undergraduate', 'Forensic Sciences'],

            // ── SED (School of Education) ──────────────────────────────────
            ['SED', 'BEED',  'Bachelor of Elementary Education',                         'Undergraduate', 'Education'],
            ['SED', 'BSNED', 'Bachelor of Special Needs Education',                      'Undergraduate', 'Education'],
            ['SED', 'BSED',  'Bachelor of Secondary Education',                          'Undergraduate', 'Education'],
            ['SED', 'BPED',  'Bachelor of Physical Education',                           'Undergraduate', 'Education'],
            ['SED', 'MATM',  'Master of Arts in Teaching Mathematics',                   "Master's",      'Graduate School'],
            ['SED', 'MATS',  'Master of Arts in Teaching Science',                       "Master's",      'Graduate School'],
            ['SED', 'MAEM',  'Master of Arts in Educational Management',                 "Master's",      'Graduate School'],
            ['SED', 'MAELLT','Master of Arts in English Language and Literature Teaching',"Master's",      'Graduate School'],
            ['SED', 'MAETF', 'Master of Arts in Education in Teaching Filipino',         "Master's",      'Graduate School'],
            ['SED', 'MAPES', 'Master of Arts in Physical Education and Sports',          "Master's",      'Graduate School'],
            ['SED', 'MARVE', 'Master of Arts in Religious and Values Education',         "Master's",      'Graduate School'],
            ['SED', 'PHDEDM','Doctor of Philosophy in Educational Management',           'Doctoral',      'Graduate School'],
            ['SED', 'PHDHLED','Doctor of Philosophy in Educational Management major in Leadership in Higher Education', 'Doctoral', 'Graduate School'],
            ['SED', 'PHDBLED','Doctor of Philosophy in Educational Management major in Leadership in Basic Education',  'Doctoral', 'Graduate School'],

            // ── SEA (School of Engineering and Architecture) ───────────────
            ['SEA', 'BSCE',  'Bachelor of Science in Civil Engineering',                 'Undergraduate', 'Engineering'],
            ['SEA', 'BSECE', 'Bachelor of Science in Electronics Engineering',           'Undergraduate', 'Engineering'],
            ['SEA', 'BSEE',  'Bachelor of Science in Electrical Engineering',            'Undergraduate', 'Engineering'],
            ['SEA', 'BSIE',  'Bachelor of Science in Industrial Engineering',            'Undergraduate', 'Engineering'],
            ['SEA', 'BSME',  'Bachelor of Science in Mechanical Engineering',            'Undergraduate', 'Engineering'],
            ['SEA', 'BSCpE', 'Bachelor of Science in Computer Engineering',              'Undergraduate', 'Engineering'],
            ['SEA', 'BSAERO','Bachelor of Science in Aeronautical Engineering',          'Undergraduate', 'Engineering'],
            ['SEA', 'BSArch','Bachelor of Science in Architecture',                      'Undergraduate', 'Architecture'],
            ['SEA', 'MEEE',  'Master in Engineering Program Major in Electrical Engineering',  "Master's", 'Graduate School'],
            ['SEA', 'MEIE',  'Master in Engineering Program Major in Industrial Engineering',  "Master's", 'Graduate School'],
            ['SEA', 'MSEM',  'Master of Science in Engineering Management',              "Master's",      'Graduate School'],
            ['SEA', 'MSECE', 'Master of Science in Electronics and Communications Engineering', "Master's", 'Graduate School'],
            ['SEA', 'DTECH', 'Doctor of Technology',                                     'Doctoral',      'Graduate School'],

            // ── SHTM (School of Hospitality and Tourism Management) ────────
            ['SHTM', 'BSHM', 'Bachelor of Science in Hospitality Management',           'Undergraduate', 'Hospitality Management'],
            ['SHTM', 'BSTM', 'Bachelor of Science in Tourism Management',               'Undergraduate', 'Tourism Management'],
            ['SHTM', 'BSIG', 'Bachelor of Science in International Gastronomy',         'Undergraduate', 'Hospitality Management'],
            ['SHTM', 'MSHTM','Master of Science in Hospitality and Tourism Management', "Master's",      'Graduate School'],
            ['SHTM', 'DBAHTM','Doctor of Business Administration with Concentration in Hospitality and Tourism Management', 'Doctoral', 'Graduate School'],

            // ── SNAMS (School of Nursing and Allied Medical Sciences) ──────
            ['SNAMS', 'BSN',  'Bachelor of Science in Nursing',                         'Undergraduate', 'Nursing'],
            ['SNAMS', 'BSRT', 'Bachelor of Science in Radiologic Technology',           'Undergraduate', 'Allied Medical Sciences'],
            ['SNAMS', 'BSMT', 'Bachelor of Science in Medical Technology',              'Undergraduate', 'Allied Medical Sciences'],
            ['SNAMS', 'MSN',  'Master of Science in Nursing',                           "Master's",      'Graduate School'],
            ['SNAMS', 'MAN',  'Master of Arts in Nursing',                              "Master's",      'Graduate School'],
            ['SNAMS', 'MSHSA','Master of Science in Health Services Administration',    "Master's",      'Graduate School'],
            ['SNAMS', 'MSRT', 'Master of Science in Radiologic Technology',             "Master's",      'Graduate School'],
            ['SNAMS', 'PHDNE','Doctor of Philosophy in Nursing Education',              'Doctoral',      'Graduate School'],

            // ── SOC (School of Computing) ──────────────────────────────────
            ['SOC', 'BSEMC', 'BS in Entertainment and Multimedia Computing with Area of Specialization in Digital Animation', 'Undergraduate', 'Computing'],
            ['SOC', 'BSIT',  'Bachelor of Science in Information Technology',           'Undergraduate', 'Computing'],
            ['SOC', 'BSCS',  'Bachelor of Science in Computer Science',                 'Undergraduate', 'Computing'],
            ['SOC', 'BSCYB', 'Bachelor of Science in Cybersecurity',                    'Undergraduate', 'Computing'],
            ['SOC', 'MIT',   'Master of Information Technology',                         "Master's",      'Graduate School'],
            ['SOC', 'PSMCYB','Professional Science Master\'s (PSM) in Cybersecurity',   "Master's",      'Graduate School'],
        ];

        // Programs that are NOT counted as accreditable per the Excel SUMMAY sheet.
        // The SUMMAY sheet lists exactly 39 accreditable programs (rows 1–39).
        // 68 total - 29 non-accreditable = 39 accreditable ✓ (matches Excel SY 2025-26: 39 accreditable, 32 accredited = 82%)
        $nonAccreditablePrograms = [
            'LES',      // Laboratory Elementary School
            'JHS',      // Junior High School
            'MAGC',     // Master of Arts in Guidance and Counselling
            'MSPSYCH',  // Master of Science in Psychology
            'MAC',      // Master of Arts in Communication
            'PHDPSYCH', // Doctor of Philosophy in Psychology
            'MDMC',     // Master in Digital Marketing Communication
            'BSIA',     // Bachelor of Science in Internal Auditing
            'BSMA',     // Bachelor of Science in Management Accounting
            'MSA',      // Master of Science in Accountancy
            'MPA',      // Master in Public Administration
            'DBA',      // Doctor of Business Administration
            'MSEM',     // Master of Science in Engineering Management
            'MEIE',     // Master in Engineering Program major in Industrial Engineering
            'MSECE',    // Master of Science in Electronics and Communications Engineering
            'DTECH',    // Doctor of Technology
            'MATS',     // Master of Arts in Teaching Science
            'MAPES',    // Master of Arts in Physical Education and Sports
            'PHDBLED',  // Doctor of Philosophy in Educational Management major in Leadership in Basic Education
            'BSIG',     // Bachelor of Science in International Gastronomy
            'DBAHTM',   // Doctor of Business Administration (SHTM concentration)
            'MSHTM',    // Master of Science in Hospitality and Tourism Management
            'MAN',      // Master of Arts in Nursing
            'MSHSA',    // Master of Science in Health Services Administration
            'MSRT',     // Master of Science in Radiologic Technology
            'PHDNE',    // Doctor of Philosophy in Nursing Education
            'BSCYB',    // Bachelor of Science in Cybersecurity
            'PSMCYB',   // Professional Science Master's in Cybersecurity
            'MARVE',    // Master of Arts in Religious and Values Education
        ];

        $programs = [];
        foreach ($programData as [$colCode, $progCode, $progName, $level, $dept]) {
            $programs[$progCode] = Program::create([
                'program_code'    => $progCode,
                'program_name'    => $progName,
                'college_id'      => $colleges[$colCode]->college_id,
                'department'      => $dept,
                'program_level'   => $level,
                'is_accreditable' => !in_array($progCode, $nonAccreditablePrograms),
            ]);
        }

        // ── 4. Accreditations (from SUMMARY sheet — current/latest status) ─
        // Format: [ program_code, accrediting_body, accrediting_type (Local/International), level_or_tier, status, expiry_date|null ]
        // Statuses: Active | Expiring Soon | Expired | Pending | None

        $accredData = [
            // BED
            ['LES',    'PAASCU',  'Local',         'Level 1',              'Pending',       null],
            ['JHS',    'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['SHS',    'PAASCU',  'Local',         'Level 1',              'Active',        null],

            // CCJEF
            ['BSCRIM', 'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-11-01'],
            ['BSCRIM', 'AUN-QA',  'International', 'Accredited',           'Active',        '2028-11-01'],
            ['BFS',    'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BFS',    'AUN-QA',  'International', 'Accredited',           'Active',        null],

            // SAS
            ['BAC',    'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BAC',    'AUN-QA',  'International', 'Accredited',           'Active',        null],
            ['BSPSYCH','PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BSPSYCH','AUN-QA',  'International', 'Accredited',           'Active',        null],
            ['MAGC',   'PACUCOA', 'Local',         'Associate',            'Active',        null],
            ['MSPSYCH','PACUCOA', 'Local',         'Associate',            'Active',        null],

            // SBA
            ['BSA',    'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BSA',    'IACBE',   'International', 'Re-Accredited',        'Active',        null],
            ['BSBA',   'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BSBA',   'IACBE',   'International', 'Re-Accredited',        'Active',        null],
            ['BSAM',   'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-03-01'],
            ['BSAM',   'IACBE',   'International', 'Re-Accredited',        'Active',        '2028-03-01'],
            ['MBA',    'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-03-01'],
            ['MBA',    'IACBE',   'International', 'Re-Accredited',        'Active',        '2028-03-01'],
            ['GMBA',   'IACBE',   'International', 'Accredited',           'Active',        null],

            // SED
            ['BEED',   'PAASCU',  'Local',         'Level 3',              'Active',        '2026-07-18'],
            ['BEED',   'AUN-QA',  'International', 'Accredited',           'Active',        '2026-07-18'],
            ['BSED',   'PAASCU',  'Local',         'Level 3',              'Active',        '2026-07-18'],
            ['BSED',   'AUN-QA',  'International', 'Accredited',           'Active',        '2026-07-18'],
            ['MAELLT', 'PACUCOA', 'Local',         'Candidate',            'Pending',       null],
            ['MARVE',  'PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['MAEM',   'PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['MAETF',  'PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['MATM',   'PACUCOA', 'Local',         'Candidate',            'Pending',       null],
            ['PHDEDM', 'PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['PHDHLED','PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['PHDBLED','PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],

            // SEA
            ['BSCE',   'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-03-01'],
            ['BSCE',   'AUN-QA',  'International', 'Accredited',           'Active',        '2028-03-01'],
            ['BSEE',   'PAASCU',  'Local',         'Level 2',              'Active',        '2027-09-18'],
            ['BSEE',   'AUN-QA',  'International', 'Accredited',           'Active',        '2027-09-18'],
            ['BSECE',  'PAASCU',  'Local',         'Level 2',              'Active',        '2027-09-18'],
            ['BSECE',  'AUN-QA',  'International', 'Accredited',           'Active',        '2027-09-18'],
            ['BSIE',   'PAASCU',  'Local',         'Level 3',              'Active',        '2026-11-14'],
            ['BSIE',   'AUN-QA',  'International', 'Accredited',           'Active',        '2026-11-14'],
            ['BSME',   'PAASCU',  'Local',         'Level 3',              'Active',        '2026-11-14'],
            ['BSME',   'AUN-QA',  'International', 'Accredited',           'Active',        '2026-11-14'],
            ['BSCpE',  'PAASCU',  'Local',         'Level 2',              'Active',        '2028-02-26'],
            ['BSCpE',  'AUN-QA',  'International', 'Accredited',           'Active',        '2028-02-26'],
            ['BSArch', 'PAASCU',  'Local',         'Level 2',              'Active',        '2028-02-26'],
            ['BSArch', 'AUN-QA',  'International', 'Accredited',           'Active',        '2028-02-26'],
            ['BSAERO', 'PACUCOA', 'Local',         'Candidate',            'Active',        '2026-11-14'],
            ['MSEM',   'PACUCOA', 'Local',         'Candidate',            'Active',        '2028-02-26'],
            ['MSEM',   'AUN-QA',  'International', 'Accredited',           'Pending',        '2028-02-26'],
            ['MEEE',   'PACUCOA', 'Local',         'Associate',            'Pending',       null],
            ['MSECE',  'PACUCOA', 'Local',         'Associate',            'Pending',       null],

            // SHTM
            ['BSHM',   'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BSHM',   'ACPHA',   'International', 'Accredited',           'Active',        null],
            ['BSTM',   'PAASCU',  'Local',         'Level 3',              'Active',        null],
            ['BSTM',   'ACPHA',   'International', 'Accredited',           'Active',        null],
            ['BSIG',   'ACPHA',   'International', 'Accredited',           'Pending',       null],
            ['MSHTM',  'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-04-01'],

            // SNAMS
            ['BSN',    'PAASCU',  'Local',         'Level 3',              'Active',        '2027-09-18'],
            ['BSN',    'AUN-QA',  'International', 'Accredited',           'Active',        '2027-09-18'],
            ['BSMT',   'PACUCOA', 'Local',         'Level 1',              'Active',        '2026-11-01'],
            ['BSRT',   'PACUCOA', 'Local',         'Level 1',              'Active',        '2026-11-01'],
            ['MSN',    'PACUCOA', 'Local',         'Level 2',              'Active',        '2027-12-01'],
            ['MSN',    'AUN-QA',  'International', 'Accredited',           'Pending',        '2027-12-01'],
            ['PHDNE',  'PACUCOA', 'Local',         'Level 2',              'Pending',       '2028-04-01'],

            // SOC
            ['BSCS',   'PACUCOA', 'Local',         'Level 2',              'Active',        '2028-04-01'],
            ['BSCS',   'AUN-QA',  'International', 'Accredited',           'Active',        '2028-04-01'],
            ['BSIT',   'PAASCU',  'Local',         'Level 2',              'Active',        '2028-02-26'],
            ['BSIT',   'AUN-QA',  'International', 'Accredited',           'Active',        '2028-02-26'],
            ['BSEMC',  'PAASCU',  'Local',         'Level 3',              'Active',        '2026-11-14'],
            ['BSEMC',  'AUN-QA',  'International', 'Accredited',           'Active',        '2026-11-14'],
            ['MIT',    'PACUCOA', 'Local',         'Level 1',              'Expired',       '2026-04-01'],
        ];

        foreach ($accredData as [$progCode, $body, $type, $level, $status, $expiry]) {
            if (!isset($programs[$progCode])) continue;
            Accreditation::create([
                'program_id'    => $programs[$progCode]->id,
                'accrediting_body' => $body,
                'type'          => $type,
                'level_or_tier' => $level,
                'status'        => $status,
                'expiry_date'   => $expiry,
                'last_visit'    => null,
            ]);
        }

        // Re-link or seed Dean and Head of Unit accounts
        // Clear old users except admin accounts
        \App\Models\User::whereNotIn('username', ['admin', 'qaoadmin'])->delete();

        // Dean of SOC
        $socCollege = \App\Models\College::where('code', 'SOC')->first();
        if ($socCollege) {
            \App\Models\User::create([
                'name' => 'Dean Computing',
                'first_name' => 'Dean',
                'last_name' => 'Computing',
                'username' => 'deansoc',
                'usertype' => 'Dean',
                'email' => 'deansoc@hau.edu.ph',
                'password' => bcrypt('password'),
                'college_id' => $socCollege->college_id,
            ]);
        }

        // QAO Unit
        $qaoUnit = \App\Models\Unit::firstOrCreate(
            ['name' => 'Quality Assurance Office'],
            ['code' => 'QAO']
        );
        \App\Models\Unit::firstOrCreate(
            ['name' => 'Office of the VPAA'],
            ['code' => 'VPAA']
        );

        // Head of QAO
        \App\Models\User::create([
            'name' => 'Head QAO',
            'first_name' => 'Head',
            'last_name' => 'QAO',
            'username' => 'headqao',
            'usertype' => 'Head of Unit',
            'email' => 'headqao@hau.edu.ph',
            'password' => bcrypt('password'),
            'unit_id' => $qaoUnit->unit_id,
        ]);

        $this->command->info('✅ HAU real data seeded: ' . count($colleges) . ' colleges, ' . count($programs) . ' programs, ' . count($accredData) . ' accreditation records.');
    }
}
