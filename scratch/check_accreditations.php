<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Accreditation;
use App\Models\Program;

// Total active accreditations
$activeAccred = Accreditation::where('status', 'Active')->count();
echo "Total Active Accreditations: " . $activeAccred . "\n";

// Total active accreditations grouped by level_or_tier
echo "\nActive Accreditations grouped by Level/Tier:\n";
$activeGrouped = Accreditation::where('status', 'Active')
    ->selectRaw('level_or_tier, count(*) as count')
    ->groupBy('level_or_tier')
    ->orderBy('count', 'desc')
    ->get();

foreach ($activeGrouped as $row) {
    echo "  {$row->level_or_tier}: {$row->count}\n";
}

// All accreditations (regardless of status) grouped by level_or_tier
echo "\nAll Accreditations (Active + Expired + Expiring Soon + Warning) grouped by Level/Tier:\n";
$allGrouped = Accreditation::selectRaw('level_or_tier, count(*) as count')
    ->groupBy('level_or_tier')
    ->orderBy('count', 'desc')
    ->get();

foreach ($allGrouped as $row) {
    echo "  {$row->level_or_tier}: {$row->count}\n";
}

// Check how many programs have at least one active accreditation
$activePrograms = Program::whereHas('accreditations', function($q) {
    $q->where('status', 'Active');
})->count();
echo "\nPrograms with at least one Active Accreditation: " . $activePrograms . "\n";

// Check how many programs have level_or_tier matching some key
echo "\nCheck programs count for level_or_tier 'Accredited' (regardless of status):\n";
$accreditedCount = Accreditation::where('level_or_tier', 'Accredited')->count();
echo "  Total 'Accredited' status rows in accreditations table: " . $accreditedCount . "\n";
$activeAccreditedCount = Accreditation::where('level_or_tier', 'Accredited')->where('status', 'Active')->count();
echo "  Active 'Accredited' status rows in accreditations table: " . $activeAccreditedCount . "\n";

// Let's dump all unique levels
echo "\nUnique levels in DB:\n";
print_r(Accreditation::distinct()->pluck('level_or_tier')->toArray());
