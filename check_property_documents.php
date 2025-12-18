<?php

/**
 * Script to check which properties have agreement documents saved in database
 * 
 * Usage: php check_property_documents.php
 * Or access via browser: http://localhost:8000/check-property-documents
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Property Documents Database Check\n";
echo "========================================\n\n";

// Get all properties with document fields
$properties = Property::select(
    'id',
    'title',
    'identity_proof',
    'national_id_passport',
    'utilities_bills',
    'power_of_attorney',
    'added_by',
    'property_classification',
    'status',
    'request_status'
)
->get();

$totalProperties = $properties->count();
$propertiesWithDocuments = 0;
$propertiesWithAllDocuments = 0;

$documentStats = [
    'identity_proof' => 0,
    'national_id_passport' => 0,
    'utilities_bills' => 0,
    'power_of_attorney' => 0,
];

$propertiesList = [];

foreach ($properties as $property) {
    $hasAnyDocument = false;
    $documentsCount = 0;
    
    $documents = [
        'identity_proof' => !empty($property->getRawOriginal('identity_proof')),
        'national_id_passport' => !empty($property->getRawOriginal('national_id_passport')),
        'utilities_bills' => !empty($property->getRawOriginal('utilities_bills')),
        'power_of_attorney' => !empty($property->getRawOriginal('power_of_attorney')),
    ];
    
    foreach ($documents as $field => $hasDocument) {
        if ($hasDocument) {
            $hasAnyDocument = true;
            $documentsCount++;
            $documentStats[$field]++;
        }
    }
    
    if ($hasAnyDocument) {
        $propertiesWithDocuments++;
        
        if ($documentsCount === 4) {
            $propertiesWithAllDocuments++;
        }
        
        $propertiesList[] = [
            'id' => $property->id,
            'title' => $property->title,
            'owner_id' => $property->added_by,
            'classification' => $property->property_classification,
            'status' => $property->status,
            'request_status' => $property->request_status,
            'documents' => $documents,
            'count' => $documentsCount,
        ];
    }
}

// Display Statistics
echo "=== STATISTICS ===\n";
echo "Total Properties: {$totalProperties}\n";
echo "Properties with at least one document: {$propertiesWithDocuments}\n";
echo "Properties with all 4 documents: {$propertiesWithAllDocuments}\n";
echo "Properties without documents: " . ($totalProperties - $propertiesWithDocuments) . "\n\n";

echo "=== DOCUMENT TYPE STATISTICS ===\n";
echo "Identity Proof: {$documentStats['identity_proof']}\n";
echo "National ID/Passport: {$documentStats['national_id_passport']}\n";
echo "Utilities Bills: {$documentStats['utilities_bills']}\n";
echo "Power of Attorney: {$documentStats['power_of_attorney']}\n\n";

// Display Properties with Documents
if (!empty($propertiesList)) {
    echo "=== PROPERTIES WITH DOCUMENTS ===\n\n";
    
    // Sort by document count (descending)
    usort($propertiesList, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    foreach ($propertiesList as $prop) {
        echo "Property ID: {$prop['id']}\n";
        echo "Title: {$prop['title']}\n";
        echo "Owner ID: {$prop['owner_id']}\n";
        echo "Classification: {$prop['classification']}\n";
        echo "Status: {$prop['status']} | Request Status: {$prop['request_status']}\n";
        echo "Documents Count: {$prop['count']}/4\n";
        echo "Documents:\n";
        echo "  - Identity Proof: " . ($prop['documents']['identity_proof'] ? '✅' : '❌') . "\n";
        echo "  - National ID/Passport: " . ($prop['documents']['national_id_passport'] ? '✅' : '❌') . "\n";
        echo "  - Utilities Bills: " . ($prop['documents']['utilities_bills'] ? '✅' : '❌') . "\n";
        echo "  - Power of Attorney: " . ($prop['documents']['power_of_attorney'] ? '✅' : '❌') . "\n";
        echo "  Admin Edit URL: http://localhost:8000/property/{$prop['id']}/edit\n";
        echo "----------------------------------------\n\n";
    }
} else {
    echo "No properties found with documents.\n\n";
}

// Export to CSV option
echo "=== EXPORT OPTIONS ===\n";
echo "To export this data to CSV, run:\n";
echo "php check_property_documents.php --export\n\n";

// If export flag is set
if (isset($argv[1]) && $argv[1] === '--export') {
    $csvFile = 'property_documents_report_' . date('Y-m-d_H-i-s') . '.csv';
    $fp = fopen($csvFile, 'w');
    
    // CSV Headers
    fputcsv($fp, [
        'Property ID',
        'Title',
        'Owner ID',
        'Classification',
        'Status',
        'Request Status',
        'Identity Proof',
        'National ID/Passport',
        'Utilities Bills',
        'Power of Attorney',
        'Documents Count',
        'Admin Edit URL'
    ]);
    
    // CSV Data
    foreach ($propertiesList as $prop) {
        fputcsv($fp, [
            $prop['id'],
            $prop['title'],
            $prop['owner_id'],
            $prop['classification'],
            $prop['status'],
            $prop['request_status'],
            $prop['documents']['identity_proof'] ? 'Yes' : 'No',
            $prop['documents']['national_id_passport'] ? 'Yes' : 'No',
            $prop['documents']['utilities_bills'] ? 'Yes' : 'No',
            $prop['documents']['power_of_attorney'] ? 'Yes' : 'No',
            $prop['count'],
            "http://localhost:8000/property/{$prop['id']}/edit"
        ]);
    }
    
    fclose($fp);
    echo "✅ Data exported to: {$csvFile}\n";
}

echo "========================================\n";

