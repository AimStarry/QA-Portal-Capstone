<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Program;
use App\Models\Accreditation;

// Let's get all programs with their active accreditations
$programs = Program::with(['accreditations' => function($q) {
    $q->where('status', 'Active');
}])->orderBy('program_code')->get();

$localCount = 0;
$intlCount = 0;
$bothCount = 0;
$localOnlyCount = 0;
$intlOnlyCount = 0;

echo "Program Code | Program Name | Active Accrediting Bodies | Categories\n";
echo "--------------------------------------------------------------------------\n";

foreach ($programs as $p) {
    $activeAccreds = $p->accreditations;
    if ($activeAccreds->isEmpty()) {
        continue;
    }
    
    $bodies = [];
    $isLocal = false;
    $isIntl = false;
    
    foreach ($activeAccreds as $acc) {
        $body = $acc->accrediting_body;
        $bodies[] = $body;
        
        if (in_array($body, ['PAASCU', 'PACUCOA'])) {
            $isLocal = true;
        }
        if (in_array($body, ['AUN-QA', 'IACBE', 'ACPHA'])) {
            $isIntl = true;
        }
    }
    
    $categoryStr = "";
    if ($isLocal && $isIntl) {
        $bothCount++;
        $categoryStr = "Both (Local & International)";
    } elseif ($isLocal) {
        $localOnlyCount++;
        $categoryStr = "Local Only";
    } elseif ($isIntl) {
        $intlOnlyCount++;
        $categoryStr = "International Only";
    }
    
    echo "{$p->program_code} | {$p->program_name} | " . implode(', ', $bodies) . " | {$categoryStr}\n";
}

echo "\nSummary of active accredited programs:\n";
echo "Local Only Programs count: {$localOnlyCount}\n";
echo "International Only Programs count: {$intlOnlyCount}\n";
echo "Both Local & International Programs count: {$bothCount}\n";
echo "Total Programs with Active Local Accreditation (Local Only + Both): " . ($localOnlyCount + $bothCount) . "\n";
echo "Total Programs with Active International Accreditation (International Only + Both): " . ($intlOnlyCount + $bothCount) . "\n";
echo "Total Accredited Programs (any active accreditation): " . ($localOnlyCount + $intlOnlyCount + $bothCount) . "\n";
