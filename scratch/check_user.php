<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$email = 'oieqao26@gmail.com';
$user = User::where('email', $email)->first();

if ($user) {
    echo "Found user: " . $user->username . " (" . $user->usertype . ")\n";
} else {
    echo "User NOT found. Updating primary admin (admin) email to: " . $email . "\n";
    $admin = User::where('username', 'admin')->first();
    if ($admin) {
        $admin->email = $email;
        $admin->save();
        echo "Successfully updated admin email to " . $email . "\n";
    } else {
        echo "Admin user not found either!\n";
    }
}
