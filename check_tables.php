<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CHECKING TABLE NAMES ===\n";

$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    foreach ($table as $tableName) {
        if (strpos($tableName, 'prop') !== false) {
            echo $tableName . "\n";
        }
    }
}
