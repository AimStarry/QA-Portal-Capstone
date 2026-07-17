<?php
$linkPath = __DIR__ . '/../public/storage';

if (file_exists($linkPath) || is_link($linkPath)) {
    echo "Existing storage link/folder found at: " . realpath($linkPath) . "\n";
    // Delete link/folder on Windows
    if (is_link($linkPath)) {
        unlink($linkPath);
        echo "Deleted symbolic link.\n";
    } else {
        // If it is a directory junction or folder
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('rmdir /s /q "' . $linkPath . '"');
            echo "Deleted directory link using rmdir.\n";
        } else {
            exec('rm -rf "' . $linkPath . '"');
            echo "Deleted directory using rm.\n";
        }
    }
} else {
    echo "No existing link/folder found.\n";
}

// Run artisan command
exec('php artisan storage:link', $output, $result);
echo implode("\n", $output) . "\n";
echo "Exit code: " . $result . "\n";
