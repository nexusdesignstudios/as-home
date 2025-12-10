<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Customer;
use App\Http\Controllers\PropertController;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TestContractEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:contract-email 
                            {property : The property ID to use for testing}
                            {--email= : Override email address for testing (optional)}
                            {--type=selling_or_renting_contract : Contract type to test (selling_or_renting_contract, basic_package_self_managed, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test contract email for a specific property';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $propertyId = $this->argument('property');
        $overrideEmail = $this->option('email');
        $contractType = $this->option('type');

        $this->info("=== Testing Contract Email ===\n");
        $this->info("Property ID: {$propertyId}");
        $this->info("Contract Type: {$contractType}");

        try {
            // Find the property
            $property = Property::with('customer')->find($propertyId);

            if (!$property) {
                $this->error("Property with ID {$propertyId} not found.");
                return Command::FAILURE;
            }

            $owner = $property->customer;
            if (!$owner) {
                $this->error("Property has no owner (customer) assigned.");
                return Command::FAILURE;
            }

            // Determine email to send to
            $emailTo = $overrideEmail ?: $owner->email;
            
            if (!$emailTo) {
                $this->error("No email address found. Provide one using --email option.");
                return Command::FAILURE;
            }

            $this->info("Owner: {$owner->name}");
            $this->info("Owner Email: {$owner->email}");
            if ($overrideEmail) {
                $this->warn("Test email override: {$overrideEmail}");
            }

            // Confirm before sending
            if (!$this->confirm('Do you want to send the test contract email?', true)) {
                $this->info('Test email cancelled.');
                return Command::SUCCESS;
            }

            // Temporarily override the owner's email if test email is provided
            $originalEmail = $owner->email;
            if ($overrideEmail) {
                $owner->email = $overrideEmail;
                $owner->save(); // Save temporarily for the email send
            }

            // Create an instance of PropertController to access the private method
            // We'll use reflection to call the private sendContractEmail method
            $controller = new PropertController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('sendContractEmail');
            $method->setAccessible(true);

            $this->info("\nSending test contract email...");

            // Call the sendContractEmail method
            $method->invoke($controller, $property, $contractType);

            // Restore original email if it was changed
            if ($overrideEmail && $originalEmail) {
                $owner->email = $originalEmail;
                $owner->save();
            }

            $this->info("\n✓ Test contract email sent successfully!");
            $this->info("Email sent to: {$emailTo}");
            $this->info("Subject: Your As-home Property Listing Contract");
            
            if (config('mail.mailers.smtp.host') === 'mailpit' || config('mail.default') === 'mailpit') {
                $this->info("\nTo view the email, open Mailpit at: http://localhost:8025");
            } else {
                $this->info("\nPlease check the inbox (and spam folder) for: {$emailTo}");
            }

            Log::info('Test contract email sent', [
                'property_id' => $propertyId,
                'contract_type' => $contractType,
                'email' => $emailTo,
                'owner_name' => $owner->name
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n✗ Email sending failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            Log::error('Test contract email failed', [
                'property_id' => $propertyId,
                'contract_type' => $contractType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}

