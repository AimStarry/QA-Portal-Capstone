<?php

namespace App\Services;

use App\Models\Accreditation;
use App\Models\College;
use App\Models\ComplianceAssignment;
use App\Models\ComplianceRecord;
use App\Models\GraduateRecord;
use App\Models\Program;
use App\Models\RecommendationItem;
use App\Models\ResponsibleUnit;
use App\Models\RiskItem;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FullReportExportService
{
    // HAU Brand Colors
    private const COLOR_MAROON = '5C0000';
    private const COLOR_MAROON_LIGHT = '7A1B28';
    private const COLOR_GOLD = 'D4AF37';
    private const COLOR_GOLD_LIGHT = 'FFF8E7';
    private const COLOR_BORDER = 'E5E7EB';
    private const COLOR_HEADER_TEXT = 'FFFFFF';

    // Status Badge Colors
    private const COLOR_STATUS_ACTIVE_BG = 'D1FAE5';
    private const COLOR_STATUS_ACTIVE_FG = '065F46';
    private const COLOR_STATUS_WARN_BG = 'FEF3C7';
    private const COLOR_STATUS_WARN_FG = '92400E';
    private const COLOR_STATUS_DANGER_BG = 'FEE2E2';
    private const COLOR_STATUS_DANGER_FG = '991B1B';
    private const COLOR_STATUS_NEUTRAL_BG = 'F3F4F6';
    private const COLOR_STATUS_NEUTRAL_FG = '374151';
    private const COLOR_STATUS_INDIGO_BG = 'E0E7FF';
    private const COLOR_STATUS_INDIGO_FG = '3730A3';

    /**
     * Generate and stream the full multi-tab system report spreadsheet.
     */
    public function export(User $user): StreamedResponse
    {
        @ini_set('max_execution_time', 300);
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Holy Angel University QA Portal')
            ->setLastModifiedBy($user->name ?? 'QA System')
            ->setTitle('HAU QA Portal Comprehensive Accreditation & Compliance Report')
            ->setSubject('Accreditation, Compliance, Risk, and Academic Matrix')
            ->setDescription('Full system report generated from the HAU QA Portal.');

        // Sheet 1: Summary
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Summary');
        $this->buildExecutiveSummarySheet($sheet1, $user);

        // Sheet 2: Accreditations
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Accreditations');
        $this->buildAccreditationsSheet($sheet2);

        // Sheet 3: Compliance Tasks
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Compliance Tasks');
        $this->buildComplianceSheet($sheet3);

        // Sheet 4: Recommendations
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Recommendations');
        $this->buildRecommendationsSheet($sheet4);

        // Sheet 5: Target Matrix
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Target Matrix');
        $this->buildTargetMatrixSheet($sheet5);

        // Sheet 6: Academic Programs
        $sheet6 = $spreadsheet->createSheet();
        $sheet6->setTitle('Academic Programs');
        $this->buildProgramsSheet($sheet6);

        // Sheet 7: Risk Monitor
        $sheet7 = $spreadsheet->createSheet();
        $sheet7->setTitle('Risk Monitor');
        $this->buildRiskSheet($sheet7);

        // Sheet 8: Graduates Tracker
        $sheet8 = $spreadsheet->createSheet();
        $sheet8->setTitle('Graduates Tracker');
        $this->buildGraduatesSheet($sheet8);

        // Sheet 9: Responsible Units
        $sheet9 = $spreadsheet->createSheet();
        $sheet9->setTitle('Responsible Units');
        $this->buildUnitsSheet($sheet9);

        // Set active sheet back to Summary
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'HAU_QA_Full_System_Report_' . date('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Build Tab 1: Summary Sheet
     */
    private function buildExecutiveSummarySheet(Worksheet $sheet, User $user): void
    {
        $sheet->setShowGridlines(true);

        // 1. University Banner Header
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'HOLY ANGEL UNIVERSITY — QUALITY ASSURANCE PORTAL');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_MAROON);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'COMPREHENSIVE ACCREDITATION & COMPLIANCE SUMMARY REPORT');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_MAROON));
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GOLD_LIGHT);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 2. Metadata Block
        $sheet->setCellValue('A4', 'Report Generation Date:');
        $sheet->setCellValue('B4', date('F d, Y — h:i A'));
        $sheet->setCellValue('A5', 'Generated By / Role:');
        $sheet->setCellValue('B5', ($user->name ?? 'Authorized User') . ' (' . ($user->usertype ?? 'QA Admin') . ')');
        $sheet->setCellValue('A6', 'Institutional Scope:');
        $sheet->setCellValue('B6', 'University-Wide (All Schools, Colleges, and Support Units)');
        $sheet->setCellValue('A7', 'Accrediting Bodies:');
        $sheet->setCellValue('B7', 'PAASCU, PACUCOA, AUN-QA, CHED, PTC, PICAB');

        $sheet->getStyle('A4:A7')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4B5563'));
        $sheet->getStyle('B4:B7')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('111827'));

        // ================= GATHER LIVE PORTAL METRICS =================
        $totalPrograms = Program::count();
        $accreditablePrograms = Program::where('is_accreditable', true)->count();
        $nonAccreditablePrograms = Program::where('is_accreditable', false)->count();

        // Accredited programs (Matches Dashboard live calculations)
        $accreditedProgramsCount = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')
            ->whereNotIn('level_or_tier', ['Candidate', 'Associate']))
            ->count();

        $candidateProgramsCount = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')
            ->whereIn('level_or_tier', ['Candidate', 'Associate']))
            ->whereDoesntHave('accreditations', fn($q) => $q->where('status', 'Active')
                ->whereNotIn('level_or_tier', ['Candidate', 'Associate']))
            ->count();

        $unaccreditedProgramsCount = max(0, $accreditablePrograms - $accreditedProgramsCount - $candidateProgramsCount);
        $accreditationRate = $accreditablePrograms > 0 ? round(($accreditedProgramsCount / $accreditablePrograms) * 100) : 0;

        $localAccreditedCount = Program::whereHas('accreditations', fn($q) => $q->where('type', 'Local')->where('status', 'Active'))->count();
        $intlAccreditedCount = Program::whereHas('accreditations', fn($q) => $q->where('type', 'International')->where('status', 'Active'))->count();

        // Level breakdown counts
        $level4Count = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')->where('level_or_tier', 'like', '%IV%'))->count();
        $level3Count = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')->where('level_or_tier', 'like', '%III%'))->count();
        $level2Count = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')->where('level_or_tier', 'like', '%II%')->where('level_or_tier', 'not like', '%III%'))->count();
        $level1Count = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')->where('level_or_tier', 'like', '%I%')->where('level_or_tier', 'not like', '%II%')->where('level_or_tier', 'not like', '%III%')->where('level_or_tier', 'not like', '%IV%'))->count();

        $totalAccreditationRecords = Accreditation::count();
        $activeAccreditations = Accreditation::where('status', 'Active')->count();
        $expiringAccreditations = Accreditation::where('status', 'Expiring Soon')->count();
        $expiredAccreditations = Accreditation::where('status', 'Expired')->count();

        $totalComplianceTasks = ComplianceRecord::count();
        $compliantTasks = ComplianceRecord::where('status', 'Compliant')->count();
        $complianceRate = $totalComplianceTasks > 0 ? round(($compliantTasks / $totalComplianceTasks) * 100) : 0;

        $totalRecs = RecommendationItem::count();
        $completedRecs = RecommendationItem::where('is_completed', true)->count();
        $recsRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;

        $totalRisks = RiskItem::count();
        $openRisks = RiskItem::whereIn('status', ['Open', 'In Progress'])->count();

        // ================= 3. PORTAL SUMMARY HIGHLIGHT CARDS (2x3 Grid) =================
        $sheet->mergeCells('A9:G9');
        $sheet->setCellValue('A9', '1. PORTAL SUMMARY HIGHLIGHTS');
        $this->applySectionHeaderStyle($sheet, 'A9:G9');
        $sheet->getRowDimension(9)->setRowHeight(24);

        // Row 1 of Cards (Rows 11-13)
        $this->renderMetricCard($sheet, 'A11', 'B13', 'ACADEMIC OFFERINGS', (string)$totalPrograms, 'Total Degree Programs', self::COLOR_MAROON, self::COLOR_GOLD_LIGHT);
        $this->renderMetricCard($sheet, 'C11', 'D13', 'ACCREDITABLE PROGRAMS', "{$accreditablePrograms} Accreditable", "{$nonAccreditablePrograms} Non-Accreditable / Basic Ed", '065F46', 'D1FAE5');
        $this->renderMetricCard($sheet, 'E11', 'G13', 'ACCREDITATION RATE', "{$accreditationRate}% of Accreditable", "{$accreditedProgramsCount} of {$accreditablePrograms} Accredited", '047857', 'ECFDF5');

        // Row 2 of Cards (Rows 15-17)
        $this->renderMetricCard($sheet, 'A15', 'B17', 'ACCREDITATION SCOPE', "{$activeAccreditations} Certifications", "{$localAccreditedCount} Local | {$intlAccreditedCount} Int'l", '1E40AF', 'EFF6FF');
        $this->renderMetricCard($sheet, 'C15', 'D17', 'QA ATTENTION CENTER', "{$expiringAccreditations} Expiring/Lapsed", "{$openRisks} Active Risks | {$candidateProgramsCount} Candidate", '92400E', 'FEF3C7');
        $this->renderMetricCard($sheet, 'E15', 'G17', 'COMPLIANCE & RECOMMENDATIONS', "{$complianceRate}% Compliant", "{$completedRecs} of {$totalRecs} Recommendations Done ({$recsRate}%)", '6B21A8', 'FAF5FF');

        // ================= 4. DETAILED INSTITUTIONAL SUMMARY MATRIX =================
        $sheet->mergeCells('A19:G19');
        $sheet->setCellValue('A19', '2. INSTITUTIONAL ACCREDITATION & COMPLIANCE SUMMARY');
        $this->applySectionHeaderStyle($sheet, 'A19:G19');
        $sheet->getRowDimension(19)->setRowHeight(24);

        $summaryRows = [
            ['Category', 'Item / Indicator', 'Total Count', 'Unit', 'Status / Progress', 'Summary Notes', 'Related Tab'],
            ['Academic Offerings', 'Total Academic Degree Programs', $totalPrograms, 'Programs', 'Active Catalog', 'Undergraduate, Graduate & Basic Education Units', 'Academic Programs'],
            ['Accreditation Scope', 'Accreditable Programs Base', $accreditablePrograms, 'Programs', 'Accreditable', 'Degree programs eligible for accreditation review', 'Academic Programs'],
            ['Accreditation Scope', 'Non-Accreditable Programs', $nonAccreditablePrograms, 'Programs', 'Exempt Units', 'Basic Ed, pilot programs, or newly approved curriculum', 'Academic Programs'],
            ['Accredited Programs', 'Accredited Programs (Level I-IV)', $accreditedProgramsCount, 'Programs', 'Active Certifications', "{$accreditedProgramsCount} of {$accreditablePrograms} eligible programs accredited", 'Academic Programs'],
            ['Accredited Programs', 'Candidate / Associate Status', $candidateProgramsCount, 'Programs', 'In Progress', 'Programs preparing for preliminary or formal survey visit', 'Academic Programs'],
            ['Accredited Programs', 'Unaccredited Programs', $unaccreditedProgramsCount, 'Programs', $unaccreditedProgramsCount > 0 ? 'Pending Assessment' : 'Full Coverage', 'Eligible programs preparing for accreditation survey', 'Academic Programs'],
            ['Accreditation Rate', 'Overall Accreditation Rate', "{$accreditationRate}%", 'Percentage', $accreditationRate >= 80 ? 'High Rate (>=80%)' : 'In Progress', 'Percentage of eligible degree programs holding active accreditation', 'Summary'],
            ['Accreditation Scope', 'Local Accreditations', $localAccreditedCount, 'Programs', 'PAASCU / PACUCOA / PTC', 'Philippine national accreditation bodies', 'Accreditations'],
            ['Accreditation Scope', 'International Recognitions', $intlAccreditedCount, 'Programs', 'AUN-QA / IACBE', 'International and regional quality certifications', 'Accreditations'],
            ['Accreditations', 'Active Certifications', $activeAccreditations, 'Certificates', "{$activeAccreditations} Active", 'Currently valid certifications across all colleges', 'Accreditations'],
            ['QA Attention Center', 'Expiring / Lapsed Soon (< 6 Mos)', $expiringAccreditations, 'Certificates', $expiringAccreditations > 0 ? 'Expiring/Lapsed' : 'Optimal', 'Accreditations approaching expiry date', 'Accreditations'],
            ['QA Attention Center', 'Expired Accreditations', $expiredAccreditations, 'Certificates', $expiredAccreditations > 0 ? 'Expired' : 'Optimal', 'Requires formal revisit application from accreditor', 'Accreditations'],
            ['Compliance Tracker', 'Total Compliance Tasks', $totalComplianceTasks, 'Tasks', "{$complianceRate}% Compliant", 'Documentation audits and action plan tasks', 'Compliance Tasks'],
            ['Compliance Tracker', 'Compliant Tasks', $compliantTasks, 'Tasks', "{$compliantTasks} Compliant", 'Tasks with approved evidence and verified documentation', 'Compliance Tasks'],
            ['Recommendations', 'Total Recommendation Checklist Items', $totalRecs, 'Items', "{$recsRate}% Completed", "{$completedRecs} of {$totalRecs} recommendation items completed", 'Recommendations'],
            ['Risk Monitor', 'Total Monitored QA Risks', $totalRisks, 'Risks', "{$openRisks} Active", 'Tracked quality gaps, non-compliance alerts, and mitigation plans', 'Risk Monitor'],
        ];

        $startRow = 21;
        $sheet->fromArray($summaryRows, null, "A{$startRow}");
        $this->applyTableHeaderStyle($sheet, "A{$startRow}:G{$startRow}");
        $sheet->getRowDimension($startRow)->setRowHeight(22);

        $lastSummaryRow = $startRow + count($summaryRows) - 1;
        $this->applyBulkTableStyle($sheet, "A" . ($startRow + 1) . ":G{$lastSummaryRow}");

        for ($r = $startRow + 1; $r <= $lastSummaryRow; $r++) {
            $statusVal = (string)$summaryRows[$r - $startRow][4];
            $this->applyStatusBadgeStyle($sheet, "E{$r}", $statusVal);
        }

        // ================= 5. ACCREDITATION LEVEL BREAKDOWN =================
        $levelStartRow = $lastSummaryRow + 2;
        $sheet->mergeCells("A{$levelStartRow}:G{$levelStartRow}");
        $sheet->setCellValue("A{$levelStartRow}", '3. ACCREDITATION LEVEL BREAKDOWN');
        $this->applySectionHeaderStyle($sheet, "A{$levelStartRow}:G{$levelStartRow}");
        $sheet->getRowDimension($levelStartRow)->setRowHeight(24);

        $levelHeaderRow = $levelStartRow + 2;
        $levelHeaders = ['Accreditation Level', 'Active Programs', '% Share of Accreditable', 'Accrediting Bodies', 'Level Description', 'Status', 'Validity Period'];
        $levelRows = [
            $levelHeaders,
            ['Level IV', $level4Count, $accreditablePrograms > 0 ? round(($level4Count / $accreditablePrograms) * 100, 1) . '%' : '0%', 'PAASCU, PACUCOA', 'Highest distinction with institutional autonomy', 'Level IV Accredited', '5 Years'],
            ['Level III', $level3Count, $accreditablePrograms > 0 ? round(($level3Count / $accreditablePrograms) * 100, 1) . '%' : '0%', 'PAASCU, PACUCOA', 'Re-accredited status with high quality standards', 'Level III Accredited', '5 Years'],
            ['Level II', $level2Count, $accreditablePrograms > 0 ? round(($level2Count / $accreditablePrograms) * 100, 1) . '%' : '0%', 'PAASCU, PACUCOA, PTC', 'Formal re-accreditation status', 'Level II Accredited', '3 to 5 Years'],
            ['Level I', $level1Count, $accreditablePrograms > 0 ? round(($level1Count / $accreditablePrograms) * 100, 1) . '%' : '0%', 'PAASCU, PACUCOA, PICAB', 'Initial formal accreditation status', 'Level I Accredited', '3 Years'],
            ['Candidate Status', $candidateProgramsCount, $accreditablePrograms > 0 ? round(($candidateProgramsCount / $accreditablePrograms) * 100, 1) . '%' : '0%', 'PAASCU, PACUCOA', 'Preliminary survey completed', 'Candidate Status', '2 Years'],
            ['Unaccredited Programs', $unaccreditedProgramsCount, $accreditablePrograms > 0 ? round(($unaccreditedProgramsCount / $accreditablePrograms) * 100, 1) . '%' : '0%', '—', 'Eligible programs scheduled for assessment', 'Unaccredited', 'Action Plan'],
            ['Non-Accreditable / Basic Ed', $nonAccreditablePrograms, '—', '—', 'Basic Ed or non-degree academic units', 'Exempt Units', 'N/A'],
        ];

        $sheet->fromArray($levelRows, null, "A{$levelHeaderRow}");
        $this->applyTableHeaderStyle($sheet, "A{$levelHeaderRow}:G{$levelHeaderRow}");
        $sheet->getRowDimension($levelHeaderRow)->setRowHeight(22);

        $lastLvlRow = $levelHeaderRow + count($levelRows) - 1;
        $this->applyBulkTableStyle($sheet, "A" . ($levelHeaderRow + 1) . ":G{$lastLvlRow}");

        for ($r = $levelHeaderRow + 1; $r <= $lastLvlRow; $r++) {
            $badgeVal = (string)$levelRows[$r - $levelHeaderRow][5];
            $this->applyStatusBadgeStyle($sheet, "F{$r}", $badgeVal);
        }

        // ================= 6. SCHOOL & COLLEGE BREAKDOWN =================
        $colStartRow = $lastLvlRow + 2;
        $sheet->mergeCells("A{$colStartRow}:G{$colStartRow}");
        $sheet->setCellValue("A{$colStartRow}", '4. SCHOOL & COLLEGE BREAKDOWN');
        $this->applySectionHeaderStyle($sheet, "A{$colStartRow}:G{$colStartRow}");
        $sheet->getRowDimension($colStartRow)->setRowHeight(24);

        $collegeHeaderRow = $colStartRow + 2;
        $collegeHeaders = ['School / College Name', 'Code', 'Total Programs', 'Accreditable Programs', 'Accredited Programs', 'Accreditation Rate (%)', 'Active Certifications'];
        
        $collegeTableData = [$collegeHeaders];
        $colleges = College::with(['programs.accreditations'])->orderBy('name')->get();
        
        $collegeRates = [];
        foreach ($colleges as $col) {
            $progCount = $col->programs->count();
            $accreditableCount = $col->programs->where('is_accreditable', true)->count();
            
            $colAccreditedPrograms = 0;
            foreach ($col->programs as $cp) {
                $hasActiveFormal = $cp->accreditations
                    ->where('status', 'Active')
                    ->whereNotIn('level_or_tier', ['Candidate', 'Associate', 'None', 'Unaccredited'])
                    ->isNotEmpty();
                if ($hasActiveFormal) {
                    $colAccreditedPrograms++;
                }
            }

            $colRate = $accreditableCount > 0 ? round(($colAccreditedPrograms / $accreditableCount) * 100, 1) : 0;
            $activeAccs = $col->programs->flatMap->accreditations->where('status', 'Active')->count();

            $collegeTableData[] = [
                $col->name,
                $col->code ?? '—',
                $progCount,
                $accreditableCount,
                $colAccreditedPrograms,
                "{$colRate}%",
                $activeAccs
            ];
            $collegeRates[] = ['rate' => $colRate, 'count' => $accreditableCount];
        }

        $sheet->fromArray($collegeTableData, null, "A{$collegeHeaderRow}");
        $this->applyTableHeaderStyle($sheet, "A{$collegeHeaderRow}:G{$collegeHeaderRow}");
        $sheet->getRowDimension($collegeHeaderRow)->setRowHeight(22);

        $lastCRow = $collegeHeaderRow + count($collegeTableData) - 1;
        $this->applyBulkTableStyle($sheet, "A" . ($collegeHeaderRow + 1) . ":G{$lastCRow}");

        foreach ($collegeRates as $idx => $meta) {
            $rowNum = $collegeHeaderRow + 1 + $idx;
            $this->applyAccreditationRateStyle($sheet, "F{$rowNum}", (float)$meta['rate'], (int)$meta['count']);
        }

        $this->setColumnWidths($sheet, [
            'A' => 38,
            'B' => 14,
            'C' => 16,
            'D' => 22,
            'E' => 22,
            'F' => 26,
            'G' => 22,
        ]);
    }

    /**
     * Render a visually pleasing Summary Metric Highlight Card
     */
    private function renderMetricCard(
        Worksheet $sheet,
        string $topLeftCell,
        string $bottomRightCell,
        string $title,
        string $mainValue,
        string $subText,
        string $primaryColor,
        string $bgColor
    ): void {
        $sheet->mergeCells("{$topLeftCell}:{$bottomRightCell}");
        
        preg_match('/([A-Z]+)(\d+)/', $topLeftCell, $topMatches);
        $topRowNum = (int)$topMatches[2];

        $cardText = strtoupper($title) . "\n" . $mainValue . "\n" . $subText;
        $sheet->setCellValue($topLeftCell, $cardText);

        $cardStyle = $sheet->getStyle("{$topLeftCell}:{$bottomRightCell}");
        $cardStyle->getAlignment()->setWrapText(true);
        $cardStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $cardStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        $cardStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
        $cardStyle->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($primaryColor));
        $cardStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($primaryColor);

        for ($r = $topRowNum; $r <= $topRowNum + 2; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(18);
        }
    }

    /**
     * Build Tab 2: Accreditations Sheet
     */
    private function buildAccreditationsSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'ACCREDITATIONS OVERVIEW', 'All evaluated degree programs, active accreditation levels, survey visits, and expiration timelines.', 'A1:K1');

        $headers = [
            'ID', 'Program Code', 'Degree / Program Title', 'School / College',
            'Accrediting Agency', 'Recognition Type', 'Level / Tier',
            'Last Survey Visit', 'Validity Expiration', 'Status', 'Certificate Link'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:K{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $accreditations = Accreditation::with(['program.college'])->orderBy('expiry_date', 'asc')->get();
        $rowsData = [];
        $badgeMeta = [];

        foreach ($accreditations as $idx => $a) {
            $rowsData[] = [
                $a->accreditation_id ?? $a->id,
                $a->program->program_code ?? '—',
                $a->program->program_name ?? '—',
                $a->program->college->name ?? 'General',
                $a->accrediting_body,
                $a->type,
                $a->level_or_tier ?? '—',
                $a->last_visit ? $a->last_visit->format('Y-m-d') : '—',
                $a->expiry_date ? $a->expiry_date->format('Y-m-d') : '—',
                $a->status,
                $a->certificate_link ?? ($a->certificate_file ? url('storage/' . $a->certificate_file) : 'None Attached')
            ];
            $badgeMeta[] = ['status' => $a->status, 'level' => $a->level_or_tier];
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:K{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:K{$lastRow}");

            foreach ($badgeMeta as $idx => $meta) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "J{$r}", $meta['status']);
                $this->applyStatusBadgeStyle($sheet, "G{$r}", $meta['level']);
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 8, 'B' => 14, 'C' => 38, 'D' => 28, 'E' => 16,
            'F' => 14, 'G' => 22, 'H' => 16, 'I' => 18, 'J' => 14, 'K' => 32
        ]);
    }

    /**
     * Build Tab 3: Compliance Tasks
     */
    private function buildComplianceSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'RECOMMENDATIONS & COMPLIANCE TRACKER', 'Documentation audits, action commitments, department responsibilities, and completion progress.', 'A1:M1');

        $headers = [
            'Task ID', 'Compliance Task Title', 'School / College',
            'Responsible Unit', 'Contact Person', 'Contact Email',
            'Priority', 'Accrediting Body', 'Area / Category', 'Overall Status',
            'Approval State', 'Progress Rate', 'Due Date'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:M{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $records = ComplianceRecord::with(['recommendationItems'])->latest('compliance_record_id')->get();
        $rowsData = [];
        $statusList = [];

        foreach ($records as $c) {
            $totalRecs = $c->recommendationItems->count();
            $doneRecs = $c->recommendationItems->where('is_completed', true)->count();
            $rateStr = $totalRecs > 0 ? round(($doneRecs / $totalRecs) * 100) . "% ({$doneRecs}/{$totalRecs})" : '0%';

            $rowsData[] = [
                $c->compliance_record_id,
                $c->title,
                $c->school ?? 'General',
                $c->responsible_unit ?? '—',
                $c->contact_person ?? '—',
                $c->contact_email ?? '—',
                $c->priority ?? 'Medium',
                $c->accrediting_body,
                ($c->category ?? '') . ' / ' . ($c->area ?? ''),
                $c->status,
                $c->approval_state ?? 'None',
                $rateStr,
                $c->due_date ? $c->due_date->format('Y-m-d') : '—'
            ];
            $statusList[] = $c->status;
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:M{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:M{$lastRow}");

            foreach ($statusList as $idx => $st) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "J{$r}", $st);
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 10, 'B' => 36, 'C' => 26, 'D' => 24, 'E' => 20, 'F' => 24,
            'G' => 12, 'H' => 16, 'I' => 22, 'J' => 14, 'K' => 14, 'L' => 16, 'M' => 14
        ]);
    }

    /**
     * Build Tab 4: Recommendations Checklist Sheet
     */
    private function buildRecommendationsSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'RECOMMENDATIONS CHECKLIST', 'Individual actionable items identified during survey visits, implementation proof, and verification.', 'A1:H1');

        $headers = [
            'Item ID', 'Task ID', 'Parent Compliance Task', 'Recommendation Description',
            'Is Completed', 'Review Status', 'Evidence Link', 'Admin Remarks'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:H{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $items = RecommendationItem::with(['complianceRecord'])->orderBy('recommendation_item_id')->get();
        $rowsData = [];
        $doneList = [];

        foreach ($items as $it) {
            $rowsData[] = [
                $it->recommendation_item_id ?? $it->id,
                $it->compliance_record_id,
                $it->complianceRecord->title ?? '—',
                $it->text,
                $it->is_completed ? 'Yes (Completed)' : 'No (Pending)',
                ucfirst(str_replace('_', ' ', $it->status ?? 'pending')),
                $it->evidence_link ?? 'No Link Attached',
                $it->admin_remarks ?? '—'
            ];
            $doneList[] = $it->is_completed;
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:H{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:H{$lastRow}");

            foreach ($doneList as $idx => $isDone) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "E{$r}", $isDone ? 'Compliant' : 'Pending');
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 10, 'B' => 10, 'C' => 32, 'D' => 45, 'E' => 18, 'F' => 16, 'G' => 32, 'H' => 28
        ]);
    }

    /**
     * Build Tab 5: Target Matrix Submissions
     */
    private function buildTargetMatrixSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'TARGET MATRIX SUBMISSIONS', 'Unit-level assignment breakdown for compliance deliverables, evidence links, and approval states.', 'A1:I1');

        $headers = [
            'Assignment ID', 'Task ID', 'Compliance Task Title', 'School / College',
            'Responsible Unit', 'Status', 'Approval State',
            'Evidence Link', 'Rejection Remarks'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:I{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $assignments = ComplianceAssignment::with(['complianceRecord', 'responsibleUnit', 'program.college'])->orderBy('id')->get();
        $rowsData = [];
        $statusList = [];

        foreach ($assignments as $as) {
            $school = $as->school_name ?: ($as->program->college->name ?? ($as->program->program_code ?? 'General'));
            $unit = $as->responsibleUnit->name ?? ($as->complianceRecord->responsible_unit ?? '—');

            $rowsData[] = [
                $as->id,
                $as->compliance_record_id,
                $as->complianceRecord->title ?? '—',
                $school,
                $unit,
                $as->status,
                $as->approval_state ?? 'None',
                $as->document_link ?? ($as->pending_document_link ?? '—'),
                $as->rejection_reason ?? '—'
            ];
            $statusList[] = $as->status;
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:I{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:I{$lastRow}");

            foreach ($statusList as $idx => $st) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "F{$r}", $st);
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 14, 'B' => 10, 'C' => 32, 'D' => 26, 'E' => 24, 'F' => 16, 'G' => 14, 'H' => 32, 'I' => 28
        ]);
    }

    /**
     * Build Tab 6: Academic Programs Directory
     */
    private function buildProgramsSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'ACADEMIC PROGRAMS DIRECTORY', 'Complete catalog of academic offerings, college affiliations, eligibility, current accreditation level, and status.', 'A1:K1');

        $headers = [
            'Program ID', 'Program Code', 'Degree / Program Title', 'School / College',
            'College Code', 'Department', 'Program Level', 'Accreditation Eligibility',
            'Current Accreditation Level', 'Primary Accrediting Body', 'Active Awards'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:K{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $programs = Program::with(['college', 'accreditations'])->orderBy('program_code')->get();
        $rowsData = [];
        $metaList = [];

        foreach ($programs as $p) {
            $activeAccs = $p->accreditations->where('status', 'Active');
            $activeCount = $activeAccs->count();
            
            $topLevel = 'Unaccredited';
            $topAgency = '—';
            
            if (!$p->is_accreditable) {
                $topLevel = 'Non-Accreditable / Exempt';
            } elseif ($activeAccs->isNotEmpty()) {
                $firstAcc = $activeAccs->first();
                $topLevel = $firstAcc->level_or_tier ?: 'Active Accredited';
                $topAgency = $firstAcc->accrediting_body ?: '—';
            }

            $rowsData[] = [
                $p->program_id ?? $p->id,
                $p->program_code,
                $p->program_name,
                $p->college->name ?? 'General',
                $p->college->code ?? '—',
                $p->department ?? '—',
                $p->program_level ?? 'Undergraduate',
                $p->is_accreditable ? 'Accreditable' : 'Non-Accreditable',
                $topLevel,
                $topAgency,
                "{$activeCount} Active Award(s)"
            ];
            $metaList[] = [
                'eligibility' => $p->is_accreditable ? 'Accreditable' : 'Non-Accreditable',
                'level' => $topLevel
            ];
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:K{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:K{$lastRow}");

            foreach ($metaList as $idx => $meta) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "H{$r}", $meta['eligibility']);
                $this->applyStatusBadgeStyle($sheet, "I{$r}", $meta['level']);
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 12, 'B' => 14, 'C' => 38, 'D' => 28, 'E' => 14,
            'F' => 20, 'G' => 16, 'H' => 22, 'I' => 24, 'J' => 18, 'K' => 20
        ]);
    }

    /**
     * Build Tab 7: Risk Monitor
     */
    private function buildRiskSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'QUALITY ASSURANCE RISK MONITOR', 'Systematic logging of quality gaps, non-compliance alerts, severity classifications, and mitigation plans.', 'A1:H1');

        $headers = [
            'Risk ID', 'Issue Description', 'Likelihood', 'Impact Severity',
            'Associated Program / College', 'Source Trigger', 'Status', 'Mitigation Action Plan'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:H{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $risks = RiskItem::with(['program.college'])->orderBy('risk_item_id')->get();
        $rowsData = [];
        $metaList = [];

        foreach ($risks as $rk) {
            $progDesc = $rk->program ? "{$rk->program->program_code} — " . ($rk->program->college->name ?? '') : 'General Institutional';

            $rowsData[] = [
                $rk->risk_item_id,
                $rk->description,
                $rk->likelihood ?? 'Medium',
                $rk->impact ?? 'Medium',
                $progDesc,
                ucfirst($rk->source_type ?? 'Manual'),
                $rk->status ?? 'Open',
                $rk->mitigation_plan ?? 'No mitigation plan specified.'
            ];
            $metaList[] = ['impact' => $rk->impact, 'status' => $rk->status];
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:H{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:H{$lastRow}");

            foreach ($metaList as $idx => $meta) {
                $r = 4 + $idx;
                $this->applyStatusBadgeStyle($sheet, "D{$r}", $meta['impact']);
                $this->applyStatusBadgeStyle($sheet, "G{$r}", $meta['status']);
            }
        }

        $this->setColumnWidths($sheet, [
            'A' => 10, 'B' => 38, 'C' => 14, 'D' => 16, 'E' => 30, 'F' => 14, 'G' => 14, 'H' => 42
        ]);
    }

    /**
     * Build Tab 8: Graduates Tracker
     */
    private function buildGraduatesSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'GRADUATES TRACKER', 'Academic year cohorts, graduate counts, and institutional degree completions.', 'A1:F1');

        $headers = [
            'Record ID', 'School Year', 'Term / Semester', 'Program Code',
            'Degree Program Title', 'Graduates Count'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:F{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $graduates = GraduateRecord::with(['program'])->orderBy('school_year', 'desc')->get();
        $rowsData = [];

        foreach ($graduates as $g) {
            $rowsData[] = [
                $g->graduate_record_id,
                $g->school_year,
                $g->term ?? 'Full Year',
                $g->program->program_code ?? '—',
                $g->program->program_name ?? '—',
                $g->graduates_count
            ];
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:F{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:F{$lastRow}");
        }

        $this->setColumnWidths($sheet, [
            'A' => 12, 'B' => 16, 'C' => 18, 'D' => 16, 'E' => 38, 'F' => 18
        ]);
    }

    /**
     * Build Tab 9: Responsible Units
     */
    private function buildUnitsSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(true);

        $this->createSheetTitleBanner($sheet, 'RESPONSIBLE UNITS & DEPARTMENTS', 'Organizational units, academic departments, laboratories, and support offices responsible for QA compliance.', 'A1:F1');

        $headers = [
            'Unit ID', 'Unit / Department Name', 'Short Code', 'Parent School / College',
            'Assigned Contact Head', 'Contact Email'
        ];

        $headerRow = 3;
        $sheet->fromArray([$headers], null, "A{$headerRow}");
        $this->applyTableHeaderStyle($sheet, "A{$headerRow}:F{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->freezePane('A4');

        $units = ResponsibleUnit::with(['parent', 'users', 'college'])->orderBy('name')->get();
        $rowsData = [];

        foreach ($units as $u) {
            $head = $u->users->first();

            $rowsData[] = [
                $u->responsible_unit_id,
                $u->name,
                $u->code ?? '—',
                $u->parent->name ?? ($u->college->name ?? 'Institutional Unit'),
                $head->name ?? '—',
                $head->email ?? '—'
            ];
        }

        if (!empty($rowsData)) {
            $sheet->fromArray($rowsData, null, 'A4');
            $lastRow = 3 + count($rowsData);
            $this->applyBulkTableStyle($sheet, "A4:F{$lastRow}");
            $sheet->setAutoFilter("A{$headerRow}:F{$lastRow}");
        }

        $this->setColumnWidths($sheet, [
            'A' => 12, 'B' => 36, 'C' => 14, 'D' => 30, 'E' => 24, 'F' => 28
        ]);
    }

    // ================= HELPER STYLING METHODS =================

    private function createSheetTitleBanner(Worksheet $sheet, string $title, string $subtitle, string $mergeRange): void
    {
        $sheet->mergeCells($mergeRange);
        $sheet->setCellValue('A1', "HOLY ANGEL UNIVERSITY — {$title}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_MAROON);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    private function applySectionHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_MAROON));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GOLD_LIGHT);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_GOLD);
    }

    private function applyTableHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::COLOR_HEADER_TEXT));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_MAROON);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('3E0000');
    }

    /**
     * High-speed bulk styling for table data range.
     */
    private function applyBulkTableStyle(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setSize(10);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
    }

    /**
     * Color-coded status badge with soft background and vibrant foreground text.
     */
    private function applyStatusBadgeStyle(Worksheet $sheet, string $cell, ?string $status): void
    {
        $style = $sheet->getStyle($cell);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getFont()->setBold(true)->setSize(9);

        $statusNorm = strtolower(trim($status ?? ''));

        if (
            str_contains($statusNorm, 'active') || 
            str_contains($statusNorm, 'compliant') || 
            str_contains($statusNorm, 'approved') || 
            str_contains($statusNorm, 'mitigated') || 
            str_contains($statusNorm, 'yes (completed)') || 
            str_contains($statusNorm, 'level iv') || 
            str_contains($statusNorm, 'high rate') ||
            $statusNorm === 'accreditable' || 
            $statusNorm === 'optimal' ||
            $statusNorm === 'closed'
        ) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_ACTIVE_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_ACTIVE_FG);
        } elseif (
            str_contains($statusNorm, 'level iii') || 
            str_contains($statusNorm, 'level ii') || 
            str_contains($statusNorm, 'level i')
        ) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_INDIGO_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_INDIGO_FG);
        } elseif (
            str_contains($statusNorm, 'expiring') || 
            str_contains($statusNorm, 'lapsed') || 
            str_contains($statusNorm, 'pending') || 
            str_contains($statusNorm, 'under review') || 
            str_contains($statusNorm, 'in progress') || 
            str_contains($statusNorm, 'candidate') || 
            str_contains($statusNorm, 'medium') || 
            str_contains($statusNorm, 'low') || 
            str_contains($statusNorm, 'in review') || 
            str_contains($statusNorm, 'no (pending)')
        ) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_WARN_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_WARN_FG);
        } elseif (
            str_contains($statusNorm, 'expired') || 
            str_contains($statusNorm, 'critical') || 
            str_contains($statusNorm, 'high') || 
            str_contains($statusNorm, 'non-compliant') || 
            str_contains($statusNorm, 'rejected') || 
            str_contains($statusNorm, 'urgent') || 
            str_contains($statusNorm, 'unaccredited')
        ) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_DANGER_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_DANGER_FG);
        } else {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_NEUTRAL_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_NEUTRAL_FG);
        }
    }

    /**
     * Color code college accreditation percentage rates.
     */
    private function applyAccreditationRateStyle(Worksheet $sheet, string $cell, float $rate, int $accreditableCount): void
    {
        $style = $sheet->getStyle($cell);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getFont()->setBold(true)->setSize(10);

        if ($accreditableCount === 0) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_NEUTRAL_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_NEUTRAL_FG);
        } elseif ($rate >= 80.0) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_ACTIVE_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_ACTIVE_FG);
        } elseif ($rate >= 50.0) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_WARN_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_WARN_FG);
        } else {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STATUS_DANGER_BG);
            $style->getFont()->getColor()->setRGB(self::COLOR_STATUS_DANGER_FG);
        }
    }

    /**
     * Set explicit column widths in batch.
     */
    private function setColumnWidths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }
}
