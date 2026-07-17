<?php
$sourceDir = __DIR__ . '/../storage/app/public/colleges/logos';
$destDir = __DIR__ . '/../storage/app/public/logos/colleges';

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (is_dir($sourceDir)) {
    $files = scandir($sourceDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $srcFile = $sourceDir . '/' . $file;
        $destFile = $destDir . '/' . $file;
        if (copy($srcFile, $destFile)) {
            echo "Copied: {$file} to logos/colleges/\n";
        } else {
            echo "Failed to copy: {$file}\n";
        }
    }
} else {
    echo "Source directory does not exist.\n";
}
echo "Done!\n";
