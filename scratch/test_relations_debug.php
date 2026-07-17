<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\College;

$deansoc = User::where('username', 'deansoc')->first();
if ($deansoc) {
    echo "Raw attributes:\n";
    print_var($deansoc->getAttributes());
    
    echo "college_id property: " . $deansoc->college_id . "\n";
    echo "college_id from getAttribute(): " . $deansoc->getAttribute('college_id') . "\n";
    
    // Check if the model has a college relation loaded
    echo "relation loaded: " . var_export($deansoc->relationLoaded('college'), true) . "\n";
}

function print_var($var) {
    echo json_encode($var, JSON_PRETTY_PRINT) . "\n";
}
