<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Customer;
use App\Http\Controllers\ApiController;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendTestPropertyEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:send-property-emails 
                            {--email=nexlancer.eg@gmail.com : Email address to send test emails to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create 3 test properties (Sell, Rent Basic, Rent Premium) and send contract emails to specified email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email');
        
        $this->info("=== Property Email Test Scenarios ===\n");
        $this->info("Target Email: {$email}\n");

        try {
            DB::beginTransaction();

            // Find or create customer
            $customer = Customer::where('email', $email)->first();
            
            if (!$customer) {
                $this->info("Customer not found. Creating new customer...");
                $customer = new Customer();
                $customer->name = 'Test User - NextLancer';
                $customer->email = $email;
                $customer->password = bcrypt('test123456'); // Default password
                $customer->mobile = '01000000000';
                $customer->address = '123 Test Street, Cairo, Egypt';
                $customer->isActive = 1;
                $customer->is_email_verified = 1;
                $customer->slug_id = Str::slug($customer->name . '-' . time());
                $customer->save();
                $this->info("✓ Customer created with ID: {$customer->id}");
            } else {
                $this->info("✓ Customer found with ID: {$customer->id}");
                $this->info("Current customer email: " . ($customer->email ?? 'NULL'));
                // Ensure email is set
                if (empty($customer->email)) {
                    $this->info("Updating customer email...");
                    $customer->email = $email;
                    $customer->save();
                    $this->info("✓ Customer email updated to: {$email}");
                } else {
                    // Update to ensure it matches the requested email
                    if ($customer->email !== $email) {
                        $this->info("Updating customer email from '{$customer->email}' to '{$email}'...");
                        $customer->email = $email;
                        $customer->save();
                        $this->info("✓ Customer email updated");
                    }
                }
            }

            // Get a valid category_id (use first available category)
            $categoryId = DB::table('categories')->where('status', 1)->value('id');
            if (!$categoryId) {
                $this->error("No active categories found. Please create a category first.");
                DB::rollBack();
                return Command::FAILURE;
            }

            $this->info("Using Category ID: {$categoryId}\n");

            // Create ApiController instance to access sendContractEmail method
            $apiController = new ApiController();
            $reflection = new \ReflectionClass($apiController);
            $method = $reflection->getMethod('sendContractEmail');
            $method->setAccessible(true);

            // Scenario 1: Sell Property
            $this->info("--- Scenario 1: Creating Sell Property ---");
            $sellProperty = $this->createTestProperty($customer->id, $categoryId, [
                'title' => 'Test Sell Property - Luxury Apartment',
                'description' => 'Beautiful 3-bedroom apartment in prime location',
                'propery_type' => 0, // Sell
                'price' => 5000000,
                'address' => '123 Test Street, Cairo, Egypt',
                'city' => 'Cairo',
                'state' => 'Cairo Governorate',
                'country' => 'Egypt',
                'latitude' => '30.0444',
                'longitude' => '31.2357',
            ]);
            
            $this->info("✓ Sell Property created with ID: {$sellProperty->id}");
            
            // Reload property with customer relationship (same as in ApiController)
            $sellProperty = Property::where('id', $sellProperty->id)
                ->select('id', 'title', 'request_status', 'added_by', 'city', 'state', 'country', 'rent_package', 'propery_type', 'property_classification')
                ->with('customer:id,name,email,management_type,address')
                ->first();
            
            if (!$sellProperty->customer) {
                $this->error("Customer relationship not found!");
                DB::rollBack();
                return Command::FAILURE;
            }
            
            if (empty($sellProperty->customer->email)) {
                $this->warn("Customer email is empty. Setting to: {$email}");
                $customer->email = $email;
                $customer->save();
                // Reload the relationship
                $sellProperty->load('customer');
            }
            
            $this->info("Customer email for email sending: " . ($sellProperty->customer->email ?? 'NULL'));
            
            // Send email
            $this->info("Sending list_property_sell_contract email...");
            $method->invoke($apiController, $sellProperty, "list_property_sell_contract");
            $this->info("✓ Email sent for Sell Property\n");

            // Scenario 2: Rent Property with Basic Package
            $this->info("--- Scenario 2: Creating Rent Property (Basic Package) ---");
            $rentBasicProperty = $this->createTestProperty($customer->id, $categoryId, [
                'title' => 'Test Rent Property - Basic Package Apartment',
                'description' => 'Cozy 2-bedroom apartment available for rent',
                'propery_type' => 1, // Rent
                'rent_package' => 'basic',
                'price' => 5000,
                'address' => '456 Rental Avenue, Giza, Egypt',
                'city' => 'Giza',
                'state' => 'Giza Governorate',
                'country' => 'Egypt',
                'latitude' => '30.0131',
                'longitude' => '31.2089',
                'property_classification' => 1, // Commercial
            ]);
            
            $this->info("✓ Rent Basic Property created with ID: {$rentBasicProperty->id}");
            
            // Reload property with customer relationship (same as in ApiController)
            $rentBasicProperty = Property::where('id', $rentBasicProperty->id)
                ->select('id', 'title', 'request_status', 'added_by', 'city', 'state', 'country', 'rent_package', 'propery_type', 'property_classification')
                ->with('customer:id,name,email,management_type,address')
                ->first();
            
            if (!$rentBasicProperty->customer) {
                $this->error("Customer relationship not found!");
                DB::rollBack();
                return Command::FAILURE;
            }
            
            if (empty($rentBasicProperty->customer->email)) {
                $this->warn("Customer email is empty. Setting to: {$email}");
                $customer->email = $email;
                $customer->save();
                $rentBasicProperty->load('customer');
            }
            
            // Send email
            $this->info("Sending basic_package_renting email...");
            $method->invoke($apiController, $rentBasicProperty, "basic_package_renting");
            $this->info("✓ Email sent for Rent Basic Property\n");

            // Scenario 3: Rent Property with Premium Package
            $this->info("--- Scenario 3: Creating Rent Property (Premium Package) ---");
            $rentPremiumProperty = $this->createTestProperty($customer->id, $categoryId, [
                'title' => 'Test Rent Property - Premium Package Villa',
                'description' => 'Luxurious 4-bedroom villa with premium amenities',
                'propery_type' => 1, // Rent
                'rent_package' => 'premium',
                'price' => 15000,
                'address' => '789 Premium Boulevard, Alexandria, Egypt',
                'city' => 'Alexandria',
                'state' => 'Alexandria Governorate',
                'country' => 'Egypt',
                'latitude' => '31.2001',
                'longitude' => '29.9187',
                'property_classification' => 1, // Commercial
            ]);
            
            $this->info("✓ Rent Premium Property created with ID: {$rentPremiumProperty->id}");
            
            // Reload property with customer relationship (same as in ApiController)
            $rentPremiumProperty = Property::where('id', $rentPremiumProperty->id)
                ->select('id', 'title', 'request_status', 'added_by', 'city', 'state', 'country', 'rent_package', 'propery_type', 'property_classification')
                ->with('customer:id,name,email,management_type,address')
                ->first();
            
            if (!$rentPremiumProperty->customer) {
                $this->error("Customer relationship not found!");
                DB::rollBack();
                return Command::FAILURE;
            }
            
            if (empty($rentPremiumProperty->customer->email)) {
                $this->warn("Customer email is empty. Setting to: {$email}");
                $customer->email = $email;
                $customer->save();
                $rentPremiumProperty->load('customer');
            }
            
            // Send email
            $this->info("Sending premium_package_renting email...");
            $method->invoke($apiController, $rentPremiumProperty, "premium_package_renting");
            $this->info("✓ Email sent for Rent Premium Property\n");

            DB::commit();

            $this->info("\n=== Test Summary ===");
            $this->info("✓ All 3 properties created successfully");
            $this->info("✓ All 3 emails sent to: {$email}");
            $this->info("\nProperty IDs:");
            $this->info("  - Sell Property: {$sellProperty->id}");
            $this->info("  - Rent Basic Property: {$rentBasicProperty->id}");
            $this->info("  - Rent Premium Property: {$rentPremiumProperty->id}");
            
            if (config('mail.mailers.smtp.host') === 'mailpit' || config('mail.default') === 'mailpit') {
                $this->info("\nTo view the emails, open Mailpit at: http://localhost:8025");
            } else {
                $this->info("\nPlease check the inbox (and spam folder) for: {$email}");
            }

            Log::info('Test property emails sent', [
                'email' => $email,
                'customer_id' => $customer->id,
                'properties' => [
                    'sell' => $sellProperty->id,
                    'rent_basic' => $rentBasicProperty->id,
                    'rent_premium' => $rentPremiumProperty->id,
                ]
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n✗ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            Log::error('Test property emails failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Create a test property with minimal required data
     *
     * @param int $customerId
     * @param int $categoryId
     * @param array $data
     * @return Property
     */
    private function createTestProperty($customerId, $categoryId, array $data)
    {
        $property = new Property();
        $property->category_id = $categoryId;
        $property->slug_id = Str::slug($data['title'] . '-' . time());
        $property->title = $data['title'];
        $property->description = $data['description'];
        $property->address = $data['address'];
        $property->propery_type = $data['propery_type'];
        $property->price = $data['price'];
        $property->city = $data['city'] ?? '';
        $property->state = $data['state'] ?? '';
        $property->country = $data['country'] ?? '';
        $property->latitude = $data['latitude'] ?? '';
        $property->longitude = $data['longitude'] ?? '';
        $property->added_by = $customerId;
        $property->status = 1;
        $property->request_status = 'pending';
        $property->property_classification = $data['property_classification'] ?? 1;
        
        // Set rent_package if provided
        if (isset($data['rent_package'])) {
            $property->rent_package = $data['rent_package'];
        }
        
        // Set a default title_image (you may want to use an actual image file)
        $property->title_image = 'test-image.jpg';
        
        $property->save();
        
        return $property;
    }
}

