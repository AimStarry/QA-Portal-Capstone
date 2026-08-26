<?php

namespace App\Services;

use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\RiskItem;
use App\Models\User;
use App\Mail\QaAdminAlertMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RiskAutoLogService
{
    // How many days before expiry to start flagging as a risk
    private const ACCREDITATION_WARN_DAYS = 90;
    // How many days before a compliance due date to flag as a deadline risk
    private const COMPLIANCE_DEADLINE_WARN_DAYS = 30;

    // ─────────────────────────────────────────────────────────
    // COMPLIANCE — event-driven hooks
    // ─────────────────────────────────────────────────────────

    /**
     * Called after a ComplianceRecord is created or updated.
     * Logs, updates, or resolves risks based on the record's current state.
     */
    public static function syncFromCompliance(ComplianceRecord $record): void
    {
        $programId = $record->program_id;

        // --- Non-Compliant → log/update risk ---
        if ($record->status === 'Non-Compliant') {
            self::upsertRisk(
                sourceType:  'compliance',
                sourceId:    $record->compliance_record_id,
                programId:   $programId,
                description: "Non-compliant requirement: {$record->title}" .
                             ($record->accrediting_body ? " ({$record->accrediting_body})" : ''),
                likelihood:  'High',
                impact:      'High',
                mitigation:  $record->action_plan ?? 'No action plan set.',
                riskStatus:  'Identified',
            );
            return;
        }

        // --- Pending + deadline approaching → log/update risk ---
        if (
            $record->status === 'Pending' &&
            $record->due_date &&
            Carbon::parse($record->due_date)->isFuture() &&
            Carbon::today()->diffInDays(Carbon::parse($record->due_date), false) <= self::COMPLIANCE_DEADLINE_WARN_DAYS
        ) {
            $daysLeft = (int) Carbon::today()->diffInDays(Carbon::parse($record->due_date));
            self::upsertRisk(
                sourceType:  'compliance',
                sourceId:    $record->compliance_record_id,
                programId:   $programId,
                description: "Compliance deadline approaching in {$daysLeft} day(s): {$record->title}" .
                             ($record->accrediting_body ? " ({$record->accrediting_body})" : ''),
                likelihood:  'Medium',
                impact:      'Medium',
                mitigation:  $record->action_plan ?? 'Monitor and ensure timely submission.',
                riskStatus:  'Monitoring',
            );
            return;
        }

        // --- Compliant / resolved → auto-resolve any linked risk ---
        if ($record->status === 'Compliant') {
            self::resolveBySource('compliance', $record->compliance_record_id);
        }
    }

    // ─────────────────────────────────────────────────────────
    // ACCREDITATION — event-driven hooks
    // ─────────────────────────────────────────────────────────

    /**
     * Called after an Accreditation is created or updated.
     */
    public static function syncFromAccreditation(Accreditation $accreditation): void
    {
        // --- Expired → log/update risk ---
        if ($accreditation->status === 'Expired') {
            self::upsertRisk(
                sourceType:  'accreditation',
                sourceId:    $accreditation->accreditation_id,
                programId:   $accreditation->program_id,
                description: "Accreditation lapsed — {$accreditation->accrediting_body}" .
                             ($accreditation->level_or_tier ? " Level/Tier {$accreditation->level_or_tier}" : '') .
                             ' expired on ' . ($accreditation->expiry_date?->format('M d, Y') ?? 'unknown date') . '.',
                likelihood:  'High',
                impact:      'High',
                mitigation:  'Initiate renewal process immediately.',
                riskStatus:  'Identified',
            );
            return;
        }

        // --- Expiring Soon → log/update risk ---
        if ($accreditation->status === 'Expiring Soon') {
            $daysLeft = $accreditation->expiry_date
                ? (int) Carbon::today()->diffInDays(Carbon::parse($accreditation->expiry_date))
                : null;
            self::upsertRisk(
                sourceType:  'accreditation',
                sourceId:    $accreditation->accreditation_id,
                programId:   $accreditation->program_id,
                description: "Accreditation expiring soon — {$accreditation->accrediting_body}" .
                             ($accreditation->level_or_tier ? " Level/Tier {$accreditation->level_or_tier}" : '') .
                             ($daysLeft !== null ? " (in {$daysLeft} day(s))" : '') . '.',
                likelihood:  'Medium',
                impact:      'High',
                mitigation:  'Schedule renewal evaluation and prepare accreditation documentation.',
                riskStatus:  'Monitoring',
            );
            return;
        }

        // --- Active (renewed) → auto-resolve any linked risk ---
        if ($accreditation->status === 'Active') {
            self::resolveBySource('accreditation', $accreditation->accreditation_id);
        }
    }

    // ─────────────────────────────────────────────────────────
    // DAILY SCAN — called by the Artisan command
    // ─────────────────────────────────────────────────────────

    /**
     * Full scan across all compliance records and accreditations.
     * Suitable for the daily scheduled job.
     * Returns a summary array for logging/reporting.
     */
    public static function runDailyScan(): array
    {
        $logged   = 0;
        $resolved = 0;

        // 1. Non-Compliant records
        foreach (ComplianceRecord::where('status', 'Non-Compliant')->get() as $record) {
            $before = self::riskExistsForSource('compliance', $record->compliance_record_id);
            self::syncFromCompliance($record);
            if (!$before) $logged++;
        }

        // 2. Pending records with deadline approaching
        $deadline = Carbon::today()->addDays(self::COMPLIANCE_DEADLINE_WARN_DAYS);
        $pendingDeadline = ComplianceRecord::where('status', 'Pending')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', Carbon::today())
            ->whereDate('due_date', '<=', $deadline)
            ->get();

        foreach ($pendingDeadline as $record) {
            $before = self::riskExistsForSource('compliance', $record->compliance_record_id);
            self::syncFromCompliance($record);
            if (!$before) $logged++;
        }

        // 3. Compliant records → resolve risks
        foreach (ComplianceRecord::where('status', 'Compliant')->get() as $record) {
            $affected = self::resolveBySource('compliance', $record->compliance_record_id);
            $resolved += $affected;
        }

        // 4. Expiring / Expired accreditations
        foreach (Accreditation::whereIn('status', ['Expiring Soon', 'Expired'])->get() as $acc) {
            $before = self::riskExistsForSource('accreditation', $acc->accreditation_id);
            self::syncFromAccreditation($acc);
            if (!$before) $logged++;
        }

        // 5. Renewed (Active) accreditations → resolve risks
        foreach (Accreditation::where('status', 'Active')->get() as $acc) {
            $affected = self::resolveBySource('accreditation', $acc->accreditation_id);
            $resolved += $affected;
        }

        return ['logged' => $logged, 'resolved' => $resolved];
    }

    // ─────────────────────────────────────────────────────────
    // INTERNAL HELPERS
    // ─────────────────────────────────────────────────────────

    /**
     * Insert or update a single auto-logged risk entry.
     * Uses (source_type + source_id) as the uniqueness key to prevent duplicates.
     * QA Admin edits to likelihood/impact/mitigation are preserved on updates.
     */
    private static function upsertRisk(
        string $sourceType,
        int    $sourceId,
        ?int   $programId,
        string $description,
        string $likelihood,
        string $impact,
        string $mitigation,
        string $riskStatus,
    ): void {
        $existing = RiskItem::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing) {
            // Update description and risk status, but preserve any QA Admin manual edits
            // to likelihood, impact, and mitigation_plan.
            $existing->update([
                'description' => $description,
                'status'      => $riskStatus,
            ]);
        } else {
            $newRisk = RiskItem::create([
                'program_id'      => $programId,
                'description'     => $description,
                'likelihood'      => $likelihood,
                'impact'          => $impact,
                'mitigation_plan' => $mitigation,
                'status'          => $riskStatus,
                'source_type'     => $sourceType,
                'source_id'       => $sourceId,
                'is_auto_logged'  => true,
            ]);

            // Dispatch email notification to QA Admins
            $recipients = User::getQaAdminRecipients();
            foreach ($recipients as $recipient) {
                try {
                    $badgeType = match($likelihood) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        default => 'info',
                    };

                    Mail::to($recipient->email)->send(new QaAdminAlertMail(
                        subjectTitle: "[QA Portal] Automated Risk Detected: {$description}",
                        badge: "Auto Risk: {$riskStatus}",
                        headline: 'Automated QA Risk Detected',
                        messageBody: "An automated risk condition was detected and logged in the QA Risk Monitor.",
                        details: [
                            'Source' => ucfirst($sourceType),
                            'Description' => $description,
                            'Likelihood / Impact' => "{$likelihood} Likelihood / {$impact} Impact",
                            'Status' => $riskStatus,
                            'Recommended Action' => $mitigation,
                        ],
                        actionUrl: route('risk.index'),
                        actionText: 'Review in Risk Monitor',
                        badgeType: $badgeType
                    ));
                } catch (\Throwable $e) {
                    Log::error("Failed to send auto risk alert email to QA Admin ({$recipient->email}): " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Mark any auto-logged risk for a given source as 'Mitigated'.
     * Returns the number of records affected.
     */
    private static function resolveBySource(string $sourceType, int $sourceId): int
    {
        return RiskItem::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('is_auto_logged', true)
            ->whereIn('status', ['Identified', 'Monitoring'])
            ->update(['status' => 'Mitigated']);
    }

    /**
     * Check whether a risk already exists for the given source.
     */
    private static function riskExistsForSource(string $sourceType, int $sourceId): bool
    {
        return RiskItem::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }
}
