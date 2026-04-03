<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;

$p = Property::find(522);
if ($p) {
    echo "Title: " . ($p->title ?? 'EMPTY') . " | Class: " . ($p->getRawOriginal('property_classification') ?? 'NULL') . "\n";
} else {
    echo "Property 522 NOT found.\n";
}
