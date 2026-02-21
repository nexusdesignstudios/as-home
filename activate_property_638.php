<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;

$propertyId = 638;
$property = Property::find($propertyId);

if ($property) {
    echo "Current Status: {$property->status}\n";
    echo "Current Request Status: {$property->request_status}\n";

    $property->status = 1;
    $property->request_status = 'approved';
    $property->save();

    echo "Property {$propertyId} updated to Active/Approved.\n";
    echo "New Status: {$property->status}\n";
    echo "New Request Status: {$property->request_status}\n";
} else {
    echo "Property {$propertyId} not found.\n";
}
