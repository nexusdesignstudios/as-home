<?php

// Debug why property 333 is not appearing in filters
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Debugging Property 333 Filter Issues ===\n\n";

// Check property 333 basic status
$property = Property::find(333);
echo "Property 333 Status Check:\n";
echo "- ID: {$property->id}\n";
echo "- Title: {$property->title}\n";
echo "- Status: {$property->status}\n";
echo "- Request Status: {$property->request_status}\n";
echo "- Classification: {$property->property_classification}\n\n";

// Check if property meets basic requirements
echo "Basic Filter Requirements Check:\n";
echo "- Status = 1: " . ($property->status == 1 ? "✅" : "❌") . "\n";
echo "- Request Status = 'approved': " . ($property->request_status == 'approved' ? "✅" : "❌") . "\n";
echo "- Classification = 4 (vacation homes): " . ($property->property_classification == 4 ? "✅" : "❌") . "\n\n";

// Test the actual query logic step by step
echo "=== Testing Query Logic Step by Step ===\n\n";

// Step 1: Check if assign_parameters logic works
echo "1. Testing assign_parameters 'studio' logic:\n";
$assignStudioExists = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->where('parameters.name', 'like', '%Bedroom%')
    ->where(function($studioQuery) {
        $studioQuery->where('assign_parameters.value', '0')
            ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
            ->orWhere('assign_parameters.value', 'Studio')
            ->orWhere('assign_parameters.value', 'STUDIO');
    })
    ->exists();

echo "   Assign parameters 'studio' exists: " . ($assignStudioExists ? "✅" : "❌") . "\n\n";

// Step 2: Check if vacation_apartments logic works for 1 bedroom
echo "2. Testing vacation_apartments '1' bedroom logic:\n";
$vacation1BedExists = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', 1)
    ->where('bedrooms', '!=', 0)
    ->exists();

echo "   Vacation apartments with 1 bedroom exists: " . ($vacation1BedExists ? "✅" : "❌") . "\n\n";

// Step 3: Check if vacation_apartments logic works for studio (0 bedrooms)
echo "3. Testing vacation_apartments '0' bedroom (studio) logic:\n";
$vacationStudioExists = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', 0)
    ->exists();

echo "   Vacation apartments with 0 bedroom exists: " . ($vacationStudioExists ? "✅" : "❌") . "\n\n";

// Step 4: Test the combined query that should match property 333
echo "4. Testing combined query for STUDIO filter:\n";
$studioQuery = Property::where(['status' => 1, 'request_status' => 'approved'])
    ->where('property_classification', 4)
    ->where(function($query) {
        // Studio logic: assign_parameters OR vacation_apartments
        $query->whereExists(function($existsQuery) {
            $existsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function($linkQuery) {
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where(function($typeQuery) {
                                    $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                                        ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                                });
                        });
                })
                ->where('parameters.name', 'like', '%Bedroom%')
                ->where(function($studioQuery) {
                    $studioQuery->where('assign_parameters.value', '0')
                        ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                        ->orWhere('assign_parameters.value', 'Studio')
                        ->orWhere('assign_parameters.value', 'STUDIO');
                });
        })
        ->orWhereHas('vacationApartments', function($aptQuery) {
            $aptQuery->where('status', 1)
                ->where('bedrooms', 0);
        });
    });

$studioCount = $studioQuery->count();
$studioResults = $studioQuery->get(['id', 'title']);

echo "   Properties matching studio filter: {$studioCount}\n";
foreach ($studioResults as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n5. Testing combined query for 1 BEDROOM filter:\n";
$oneBedQuery = Property::where(['status' => 1, 'request_status' => 'approved'])
    ->where('property_classification', 4)
    ->whereHas('vacationApartments', function($aptQuery) {
        $aptQuery->where('status', 1)
            ->where('bedrooms', 1)
            ->where('bedrooms', '!=', 0);
    });

$oneBedCount = $oneBedQuery->count();
$oneBedResults = $oneBedQuery->get(['id', 'title']);

echo "   Properties matching 1 bedroom filter: {$oneBedCount}\n";
foreach ($oneBedResults as $prop) {
    echo "   - ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n=== Conclusion ===\n";
echo "Property 333 should appear in:\n";
echo "- Studio filter: " . ($studioCount > 0 ? "✅ YES" : "❌ NO") . "\n";
echo "- 1 bedroom filter: " . ($oneBedCount > 0 ? "✅ YES" : "❌ NO") . "\n";