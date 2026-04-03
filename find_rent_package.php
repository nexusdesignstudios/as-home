<?php
// Simple script to find rent package for Property 522
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;

$p = Property::find(522);
if ($p) {
    echo $p->rent_package;
} else {
    echo "NOT FOUND";
}
