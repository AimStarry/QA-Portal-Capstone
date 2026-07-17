<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\College;

$deansoc = User::where('username', 'deansoc')->first();
if ($deansoc) {
    echo "User deansoc info:\n";
    echo "  college_id: " . var_export($deansoc->college_id, true) . "\n";
    
    // Check college in db
    $college = College::find($deansoc->college_id);
    if ($college) {
        echo "  College in DB: " . $college->name . " (ID: " . $college->college_id . ")\n";
    } else {
        echo "  College in DB: NOT FOUND for ID: " . $deansoc->college_id . "\n";
    }
    
    // Test relation query
    $relation = $deansoc->college();
    echo "  Relation SQL: " . $relation->toSql() . "\n";
    
    // Test actual relation retrieval
    $relatedCollege = $deansoc->college;
    if ($relatedCollege) {
        echo "  Related College: " . $relatedCollege->name . "\n";
    } else {
        echo "  Related College: NULL\n";
    }
} else {
    echo "User deansoc not found!\n";
}

$headqao = User::where('username', 'headqao')->first();
if ($headqao) {
    echo "User headqao info:\n";
    echo "  unit_id: " . var_export($headqao->unit_id, true) . "\n";
    
    $relatedUnit = $headqao->unit;
    if ($relatedUnit) {
        echo "  Related Unit: " . $relatedUnit->name . "\n";
    } else {
        echo "  Related Unit: NULL\n";
    }
}
