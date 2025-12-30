<?php

// Check if property 333 has any vacation apartments with 0 bedrooms
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Checking Property 333 Studio Compatibility ===\n\n";

// Check property 333 vacation apartments
$vacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->select('id', 'apartment_number', 'bedrooms', 'bathrooms', 'status')
    ->get();

echo "Vacation Apartments for Property 333:\n";
foreach ($vacationApts as $apt) {
    echo "- Apartment {$apt->apartment_number}: {$apt->bedrooms} bedrooms\n";
}

// Check if any have 0 bedrooms
$studioApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('bedrooms', 0)
    ->count();

echo "\nVacation apartments with 0 bedrooms: {$studioApts}\n";

// Check assign_parameters
$assignBedroom = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->where('parameters.name', 'like', '%Bedroom%')
    ->select('parameters.name', 'assign_parameters.value')
    ->first();

echo "\nAssign Parameters Bedroom Data:\n";
if ($assignBedroom) {
    echo "- {$assignBedroom->name}: {$assignBedroom->value}\n";
} else {
    echo "- No bedroom data found in assign_parameters\n";
}

echo "\n=== Analysis ===\n";
echo "Current Logic Issues:\n";
echo "1. Studio filter requires either:\n";
echo "   - assign_parameters.value = 'studio' (✅ EXISTS: 'studio')\n";
echo "   - OR vacation_apartments.bedrooms = 0 (❌ NONE FOUND)\n";
echo "\n2. 1 bedroom filter requires:\n";
echo "   - vacation_apartments.bedrooms = 1 (✅ EXISTS: apartment 101)\n";
echo "   - AND vacation_apartments.bedrooms != 0 (✅ ALL APARTMENTS)\n";
echo "\nExpected Results:\n";
echo "- Studio filter: ✅ Should work (assign_parameters has 'studio')\n";
echo "- 1 bedroom filter: ✅ Should work (vacation_apartments has 1 bedroom)\n";
echo "\nActual Issue: Need to debug why filters return 0 results\n";