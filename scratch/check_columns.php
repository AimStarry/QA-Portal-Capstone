<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('recommendation_items');
echo "Columns in recommendation_items table:" . PHP_EOL;
foreach ($columns as $c) {
    echo "- $c" . PHP_EOL;
}
