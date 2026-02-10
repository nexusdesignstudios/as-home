<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where('email', 'nexlancer.g@gmail.com')->first();
if ($user) {
    echo "User Found: ID=" . $user->id . ", Type=" . $user->type . "\n";
} else {
    echo "User nexlancer.g@gmail.com not found\n";
}
