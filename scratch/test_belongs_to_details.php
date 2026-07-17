<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$deansoc = User::where('username', 'deansoc')->first();
if ($deansoc) {
    $relation = $deansoc->college();
    echo "Foreign Key Name: " . $relation->getForeignKeyName() . "\n";
    echo "Owner Key Name: " . $relation->getOwnerKeyName() . "\n";
    
    // In newer Laravel version, we can get parent/foreign key values:
    echo "Parent (User) Key (value of foreign key): " . var_export($relation->getParentKey(), true) . "\n";
}
