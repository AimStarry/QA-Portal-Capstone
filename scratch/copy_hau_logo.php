<?php
$sourceFile = __DIR__ . '/../storage/app/public/logos/units/GyNQE2zIDEzgHPrMdNp5WpeP1ZeR1wSj1FkxTYEd.png';
if (!file_exists($sourceFile)) {
    $sourceFile = __DIR__ . '/../storage/app/public/logos/units/czmmFW9J51bzPQAL8NjBi8v1lUIMT3zjchkdooD5.png';
}

$destDir = __DIR__ . '/../public/images';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

$destFile = $destDir . '/hau_logo.png';

if (file_exists($sourceFile)) {
    if (copy($sourceFile, $destFile)) {
        echo "Successfully copied logo to: " . realpath($destFile) . "\n";
    } else {
        echo "Failed to copy logo.\n";
    }
} else {
    echo "Source logo file not found.\n";
}
