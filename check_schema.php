<?php

use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking columns in hotel_rooms table...\n";
$columns = Schema::getColumnListing('hotel_rooms');

foreach ($columns as $column) {
    echo "- $column\n";
}

$missing = [];
$expected = ['base_guests', 'min_guests', 'max_guests', 'guest_pricing_rules'];

foreach ($expected as $exp) {
    if (!in_array($exp, $columns)) {
        $missing[] = $exp;
    }
}

if (count($missing) > 0) {
    echo "\nMISSING COLUMNS:\n";
    foreach ($missing as $m) {
        echo "X $m\n";
    }
} else {
    echo "\nAll expected columns are present.\n";
}
