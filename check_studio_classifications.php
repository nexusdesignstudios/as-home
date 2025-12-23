<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "Checking Studio Properties Classifications:\n\n";

$properties = Property::whereIn('id', [314, 331, 333, 339])
    ->get(['id', 'title', 'property_classification']);

foreach ($properties as $prop) {
    $raw = $prop->getRawOriginal('property_classification');
    $accessor = $prop->property_classification;
    echo "ID {$prop->id}: {$prop->title}\n";
    echo "  Raw: {$raw}, Accessor: {$accessor}\n";
    echo "  Is vacation home (raw == 4): " . ($raw == 4 ? 'YES' : 'NO') . "\n\n";
}

