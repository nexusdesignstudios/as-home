<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\PropertyQuestionField;
use App\Models\PropertyQuestionAnswer;
use App\Models\PropertyQuestionFieldValue;
use Illuminate\Support\Facades\DB;

class CreateSampleFeedbackAnswers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-sample-feedback-answers 
                            {--property-id= : Specific hotel property ID to use}
                            {--property-name= : Search for property by name (partial match)}
                            {--count=3 : Number of sample submissions to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample feedback answers for a hotel property to test the answers view';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $propertyId = $this->option('property-id');
        $propertyName = $this->option('property-name');
        $count = (int)$this->option('count');

        $this->info("Creating {$count} sample feedback submissions...\n");

        try {
            // Find a hotel property
            if ($propertyId) {
                $property = Property::where('property_classification', 5)->find($propertyId);
            } elseif ($propertyName) {
                $property = Property::where('property_classification', 5)
                    ->where('title', 'like', "%{$propertyName}%")
                    ->with('customer')
                    ->first();
                if ($property) {
                    $this->info("✓ Found property matching '{$propertyName}': ID {$property->id} - {$property->title}");
                } else {
                    $this->warn("No property found matching '{$propertyName}'");
                    $this->info("Searching all hotels...");
                    $property = Property::where('property_classification', 5)
                        ->with('customer')
                        ->first();
                }
            } else {
                $property = Property::where('property_classification', 5)
                    ->with('customer')
                    ->first();
            }

            if (!$property) {
                $this->error("No hotel property found.");
                if ($propertyId) {
                    $this->error("Property ID {$propertyId} not found or is not a hotel property (classification 5).");
                }
                return Command::FAILURE;
            }

            $this->info("✓ Using Hotel Property:");
            $this->info("  - ID: {$property->id}");
            $this->info("  - Name: {$property->title}");
            $this->info("  - Owner: " . ($property->customer->name ?? 'N/A'));

            // Get active question fields for hotel booking (classification 5)
            $fields = PropertyQuestionField::where('property_classification', 5)
                ->where('status', 'active')
                ->with('field_values')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($fields->isEmpty()) {
                $this->warn("⚠ No active question fields found for hotel booking (classification 5).");
                $this->info("Creating sample question fields first...");
                
                // Create sample question fields
                $sampleFields = [
                    ['name' => 'Overall Rating', 'type' => 'dropdown', 'values' => ['1', '2', '3', '4', '5']],
                    ['name' => 'Room Cleanliness', 'type' => 'dropdown', 'values' => ['1', '2', '3', '4', '5']],
                    ['name' => 'Staff Service', 'type' => 'dropdown', 'values' => ['1', '2', '3', '4', '5']],
                    ['name' => 'Would you recommend this hotel?', 'type' => 'radio', 'values' => ['Yes', 'No', 'Maybe']],
                    ['name' => 'Additional Comments', 'type' => 'textarea'],
                ];

                foreach ($sampleFields as $fieldData) {
                    $field = PropertyQuestionField::create([
                        'name' => $fieldData['name'],
                        'field_type' => $fieldData['type'],
                        'property_classification' => 5,
                        'status' => 'active',
                        'rank' => null,
                    ]);

                    if (in_array($fieldData['type'], ['dropdown', 'radio']) && isset($fieldData['values'])) {
                        foreach ($fieldData['values'] as $value) {
                            PropertyQuestionFieldValue::create([
                                'property_question_field_id' => $field->id,
                                'value' => $value,
                            ]);
                        }
                    }

                    $this->info("  Created field: {$fieldData['name']}");
                }

                // Reload fields
                $fields = PropertyQuestionField::where('property_classification', 5)
                    ->where('status', 'active')
                    ->with('field_values')
                    ->orderBy('created_at', 'asc')
                    ->get();
            }

            $this->info("\n✓ Found " . $fields->count() . " active question fields");

            // Find hotel reservations for this property
            $reservations = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                ->whereIn('status', ['confirmed', 'approved', 'completed'])
                ->with(['customer', 'reservable.property'])
                ->get()
                ->filter(function($reservation) use ($property) {
                    // Filter to only reservations for this property
                    if ($reservation->reservable && $reservation->reservable->property_id == $property->id) {
                        return true;
                    }
                    return false;
                })
                ->take($count);

            // If not enough reservations, create more
            if ($reservations->count() < $count) {
                $needed = $count - $reservations->count();
                $this->warn("⚠ Only found {$reservations->count()} reservation(s), need {$needed} more.");
                $this->info("Creating additional sample reservations...");
                
                // Try to find any hotel reservation
                $reservations = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                    ->whereIn('status', ['confirmed', 'approved', 'completed'])
                    ->with(['customer', 'reservable.property'])
                    ->limit($count)
                    ->get();

                // Filter to ensure property matches
                $reservations = $reservations->filter(function($res) use ($property) {
                    if ($res->reservable && $res->reservable->property) {
                        return $res->reservable->property->id == $property->id;
                    }
                    return false;
                });

                if ($reservations->isEmpty()) {
                    $this->error("No hotel reservations found. Creating sample reservations...");
                    
                    // Get customers
                    $customers = Customer::limit($count)->get();
                    if ($customers->isEmpty()) {
                        $this->error("No customers found in database.");
                        return Command::FAILURE;
                    }

                    // Get hotel room for this property
                    $hotelRoom = \App\Models\HotelRoom::where('property_id', $property->id)->first();
                    if (!$hotelRoom) {
                        $this->error("No hotel rooms found for this property.");
                        return Command::FAILURE;
                    }

                    // Create sample reservations
                    $createdReservations = collect();
                    $needed = $count - $reservations->count();
                    
                    // Get more customers if needed
                    $allCustomers = Customer::limit($needed + 10)->get();
                    if ($allCustomers->count() < $needed) {
                        $this->warn("Only {$allCustomers->count()} customers available, creating {$allCustomers->count()} reservations.");
                        $needed = $allCustomers->count();
                    }
                    
                    foreach ($allCustomers->take($needed) as $index => $customer) {
                        $reservation = Reservation::create([
                            'customer_id' => $customer->id,
                            'reservable_id' => $hotelRoom->id,
                            'reservable_type' => 'App\\Models\\HotelRoom',
                            'check_in_date' => now()->subDays(30 - ($index * 2)),
                            'check_out_date' => now()->subDays(28 - ($index * 2)),
                            'number_of_guests' => 2,
                            'total_price' => 500 + ($index * 100),
                            'status' => 'completed',
                            'payment_status' => 'paid',
                        ]);
                        $reservation->load('customer');
                        $createdReservations->push($reservation);
                        $this->info("  Created reservation ID: {$reservation->id} for customer: {$customer->name}");
                    }
                    $reservations = $reservations->merge($createdReservations);
                }
            }

            $this->info("\n✓ Found/Using " . $reservations->count() . " reservations");

            // Create sample answers for each reservation
            $createdCount = 0;
            foreach ($reservations as $index => $reservation) {
                $customer = $reservation->customer;
                if (!$customer) {
                    $this->warn("Skipping reservation {$reservation->id}: No customer");
                    continue;
                }

                // Check if answers already exist for this reservation
                $existing = PropertyQuestionAnswer::where('reservation_id', $reservation->id)
                    ->where('property_id', $property->id)
                    ->where('customer_id', $customer->id)
                    ->exists();

                if ($existing) {
                    $this->warn("Reservation {$reservation->id} already has answers, skipping...");
                    continue;
                }

                $this->info("\nCreating answers for:");
                $this->info("  - Reservation ID: {$reservation->id}");
                $this->info("  - Customer: {$customer->name} ({$customer->email})");

                // Create answers for each field
                foreach ($fields as $field) {
                    $value = $this->generateSampleValue($field);
                    
                    PropertyQuestionAnswer::create([
                        'property_id' => $property->id,
                        'customer_id' => $customer->id,
                        'reservation_id' => $reservation->id,
                        'property_question_field_id' => $field->id,
                        'value' => $value,
                    ]);

                    $this->line("    ✓ {$field->name}: {$value}");
                }

                $createdCount++;
            }

            $this->info("\n=== Summary ===");
            $this->info("✓ Created {$createdCount} feedback submissions");
            $this->info("✓ Property ID: {$property->id}");
            $this->info("✓ Property Name: {$property->title}");
            $this->info("\nTo view the answers:");
            $this->info("1. Go to Admin Panel → Property Question Form");
            $this->info("2. Select Property → Choose property ID: {$property->id}");
            $this->info("3. View all submitted answers with customer info and reservation IDs");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Generate sample value based on field type
     */
    private function generateSampleValue($field)
    {
        switch ($field->field_type) {
            case 'dropdown':
            case 'radio':
                if ($field->field_values->isNotEmpty()) {
                    // For rating fields (1-5), randomly pick from available values
                    $values = $field->field_values->pluck('value')->toArray();
                    return $values[array_rand($values)];
                }
                return 'Sample Value';
            
            case 'checkbox':
                if ($field->field_values->isNotEmpty()) {
                    $values = $field->field_values->pluck('value')->random(min(2, $field->field_values->count()));
                    return json_encode($values->toArray());
                }
                return json_encode(['Option 1', 'Option 2']);
            
            case 'textarea':
                $samples = [
                    'Great experience! Very clean and comfortable.',
                    'Excellent service and friendly staff.',
                    'The room was spacious and well-maintained.',
                    'Had a wonderful stay, would definitely come back!',
                    'Everything was perfect, highly recommended.',
                ];
                return $samples[array_rand($samples)];
            
            case 'number':
                return rand(1, 10);
            
            case 'text':
            default:
                return 'Sample feedback answer';
        }
    }
}
