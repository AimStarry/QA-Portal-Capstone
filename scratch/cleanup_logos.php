<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\College;

$colleges = College::all();
foreach ($colleges as $college) {
    if ($college->logo && str_starts_with($college->logo, 'storage/')) {
        $old = $college->logo;
        // Strip 'storage/' prefix
        $new = substr($college->logo, 8);
        
        // Also update subfolder name if it was colleges/logos
        if (str_starts_with($new, 'colleges/logos/')) {
            $new = 'logos/colleges/' . substr($new, 15);
        }
        
        $college->logo = $new;
        $college->save();
        echo "Cleaned logo path for {$college->name}: '{$old}' -> '{$new}'\n";
    }
}
echo "Done!\n";
