<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Accreditation;
use Illuminate\Support\Facades\DB;

// Query active certificates breakdown by accrediting agency
$agencies = Accreditation::where('status', 'Active')
    ->select('accrediting_body', DB::raw('count(*) as count'))
    ->groupBy('accrediting_body')
    ->orderByDesc('count')
    ->get();

echo "=== BY AGENCY ===" . PHP_EOL;
foreach ($agencies as $a) {
    echo "- {$a->accrediting_body}: {$a->count} active certificates" . PHP_EOL;
}
echo PHP_EOL;

// Query active certificates breakdown by College
$colleges = Accreditation::where('status', 'Active')
    ->join('programs', 'accreditations.program_id', '=', 'programs.id')
    ->leftJoin('colleges', 'programs.college_id', '=', 'colleges.id')
    ->select('colleges.name as college_name', 'colleges.code as college_code', DB::raw('count(accreditations.id) as count'))
    ->groupBy('colleges.id', 'colleges.name', 'colleges.code')
    ->orderByDesc('count')
    ->get();

echo "=== BY SCHOOL / COLLEGE ===" . PHP_EOL;
foreach ($colleges as $c) {
    $name = $c->college_name ?: 'Unassigned / Support Unit';
    $code = $c->college_code ? " ({$c->college_code})" : '';
    echo "- {$name}{$code}: {$c->count} active certificates" . PHP_EOL;
}
echo PHP_EOL;

// List programs with active certificates
$programs = Accreditation::where('accreditations.status', 'Active')
    ->join('programs', 'accreditations.program_id', '=', 'programs.id')
    ->select('programs.program_code', 'programs.program_name', 'accreditations.accrediting_body', 'accreditations.level_or_tier')
    ->orderBy('programs.program_code')
    ->get();

echo "=== DETAILED ACTIVE LIST (Total: " . count($programs) . ") ===" . PHP_EOL;
foreach ($programs as $p) {
    echo "- [{$p->program_code}] {$p->program_name} -> {$p->accrediting_body} (Level: {$p->level_or_tier})" . PHP_EOL;
}
