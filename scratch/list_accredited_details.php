<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Accreditation;

$list = Accreditation::with('program')
    ->where('level_or_tier', 'Accredited')
    ->get();

echo "Accreditation records with level_or_tier = 'Accredited':\n";
foreach ($list as $idx => $item) {
    echo ($idx + 1) . ". Program: " . ($item->program->program_code ?? 'N/A') . " | Body: " . $item->accrediting_body . " | Status: " . $item->status . "\n";
}
