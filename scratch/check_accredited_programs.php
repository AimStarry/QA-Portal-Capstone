<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Program;

// Query 1: is_accreditable = true and has any active accreditation
$count1 = Program::where('is_accreditable', true)
    ->whereHas('accreditations', function($q) {
        $q->where('status', 'Active');
    })->count();

// Query 2: has any active accreditation (regardless of is_accreditable)
$count2 = Program::whereHas('accreditations', function($q) {
    $q->where('status', 'Active');
})->count();

// Query 3: is_accreditable = true
$count3 = Program::where('is_accreditable', true)->count();

// Query 4: total programs
$count4 = Program::count();

echo "Query 1 (is_accreditable = true & has active accreditation): " . $count1 . "\n";
echo "Query 2 (has active accreditation, regardless of is_accreditable): " . $count2 . "\n";
echo "Query 3 (is_accreditable = true, regardless of accreditation): " . $count3 . "\n";
echo "Query 4 (total programs): " . $count4 . "\n";

// Let's see the level_or_tier of active accreditations
echo "\nActive Accreditations for Accreditable Programs:\n";
$accreditedLevelCount = \App\Models\Accreditation::where('status', 'Active')
    ->whereHas('program', function($q) {
        $q->where('is_accreditable', true);
    })->count();
echo "  Total active accreditations for accreditable programs: " . $accreditedLevelCount . "\n";

// Let's see the count of programs that have Level 1, 2, 3, Re-Accredited, or Accredited as active level
$countA = Program::where('is_accreditable', true)
    ->whereHas('accreditations', function($q) {
        $q->where('status', 'Active')
          ->whereIn('level_or_tier', ['Level 1', 'Level 2', 'Level 3', 'Re-Accredited', 'Accredited']);
    })->count();
echo "  Accreditable programs with active Level 1/2/3/Re-Accredited/Accredited: " . $countA . "\n";

$countB = Program::where('is_accreditable', true)
    ->whereHas('accreditations', function($q) {
        $q->where('status', 'Active')
          ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
    })->count();
echo "  Accreditable programs with active status NOT IN Candidate/Associate: " . $countB . "\n";
