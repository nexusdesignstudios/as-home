<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\PropertyQuestionAnswer;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class VerifyFeedbackSubmission extends Command
{
    protected $signature = 'test:verify-feedback-submission 
                            {--reservation-id= : Reservation ID to check}
                            {--property-id= : Property ID to check}';
    
    protected $description = 'Verify feedback submissions and check if they appear correctly in the answers view';

    public function handle()
    {
        $this->info("=== Feedback Submission Verification ===\n");

        try {
            $reservationId = $this->option('reservation-id');
            $propertyId = $this->option('property-id');

            if ($propertyId) {
                $this->verifyByProperty($propertyId);
            } elseif ($reservationId) {
                $this->verifyByReservation($reservationId);
            } else {
                $this->error("Please provide --reservation-id or --property-id");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function verifyByProperty($propertyId)
    {
        $property = Property::find($propertyId);
        
        if (!$property) {
            $this->error("Property ID {$propertyId} not found.");
            return;
        }

        $this->info("Property: ID {$propertyId} - {$property->title}");
        $this->info("Classification: {$property->getRawOriginal('property_classification')}\n");

        // Get all answers for this property
        $answers = PropertyQuestionAnswer::where('property_id', $propertyId)
            ->with(['customer', 'reservation', 'property_question_field'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($answers->isEmpty()) {
            $this->warn("No answers found for this property.");
            return;
        }

        // Group by reservation
        $byReservation = $answers->groupBy('reservation_id');
        
        $this->info("=== Summary ===");
        $this->info("Total Answers: " . $answers->count());
        $this->info("Total Submissions: " . $byReservation->count());
        $this->info("Unique Customers: " . $answers->pluck('customer_id')->unique()->count());

        $this->info("\n=== By Reservation ===");
        foreach ($byReservation->take(10) as $resId => $resAnswers) {
            $firstAnswer = $resAnswers->first();
            $customer = $firstAnswer->customer;
            $reservation = $firstAnswer->reservation;
            
            $this->info("\nReservation ID: {$resId}");
            $customerName = $customer ? ($customer->name ?? 'N/A') : 'N/A';
            $customerEmail = $customer ? ($customer->email ?? 'N/A') : 'N/A';
            $this->info("  Customer: {$customerName} ({$customerEmail})");
            $this->info("  Answers: " . $resAnswers->count());
            $this->info("  Submitted: " . ($firstAnswer->created_at ? $firstAnswer->created_at->format('Y-m-d H:i:s') : 'N/A'));
            
            if ($reservation) {
                $this->info("  Check-out: " . ($reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : 'N/A'));
                $this->info("  Feedback Token: " . ($reservation->feedback_token ? '✓ Set' : '✗ Not Set'));
                $this->info("  Email Sent: " . ($reservation->feedback_email_sent_at ? $reservation->feedback_email_sent_at->format('Y-m-d H:i:s') : '✗ Not Sent'));
            }
        }

        // Check for issues
        $this->info("\n=== Verification Checks ===");
        
        $issues = [];
        
        // Check for answers without customer
        $noCustomer = $answers->filter(fn($a) => !$a->customer_id || !$a->customer)->count();
        if ($noCustomer > 0) {
            $issues[] = "⚠ {$noCustomer} answer(s) without customer";
        }
        
        // Check for answers without reservation
        $noReservation = $answers->filter(fn($a) => !$a->reservation_id)->count();
        if ($noReservation > 0) {
            $issues[] = "⚠ {$noReservation} answer(s) without reservation ID";
        }
        
        // Check for missing feedback tokens
        $reservationsWithAnswers = $answers->pluck('reservation_id')->unique()->filter();
        $reservations = Reservation::whereIn('id', $reservationsWithAnswers)->get();
        $noToken = $reservations->filter(fn($r) => !$r->feedback_token)->count();
        if ($noToken > 0) {
            $issues[] = "⚠ {$noToken} reservation(s) with answers but no feedback token";
        }
        
        if (empty($issues)) {
            $this->info("✓ All checks passed!");
        } else {
            foreach ($issues as $issue) {
                $this->warn($issue);
            }
        }

        $this->info("\n=== View in Admin Panel ===");
        $this->info("Go to: Property Question Form → Select Property → ID {$propertyId}");
        $this->info("You should see all " . $answers->count() . " answers with customer info and reservation IDs.");
    }

    private function verifyByReservation($reservationId)
    {
        $reservation = Reservation::with(['customer', 'reservable'])->find($reservationId);
        
        if (!$reservation) {
            $this->error("Reservation ID {$reservationId} not found.");
            return;
        }

        $this->info("Reservation ID: {$reservationId}");
        $this->info("Customer: " . ($reservation->customer->name ?? 'N/A'));
        
        // Get property
        $property = null;
        if ($reservation->reservable_type === 'App\\Models\\Property') {
            $property = $reservation->reservable;
        } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
            $property = $reservation->reservable->property ?? null;
        }

        if (!$property) {
            $this->error("Could not determine property.");
            return;
        }

        $this->info("Property: ID {$property->id} - {$property->title}\n");

        // Get answers for this reservation
        $answers = PropertyQuestionAnswer::where('reservation_id', $reservationId)
            ->where('property_id', $property->id)
            ->with(['customer', 'property_question_field'])
            ->get();

        if ($answers->isEmpty()) {
            $this->warn("No answers found for this reservation.");
            $this->info("Feedback link: " . ($reservation->feedback_token ? 
                config('app.url') . "/feedback/{$reservation->feedback_token}" : 
                "Token not set"));
            return;
        }

        $this->info("=== Answers Found ===");
        $this->info("Total: {$answers->count()}");
        
        foreach ($answers as $answer) {
            $this->line("  • {$answer->property_question_field->name}: {$answer->value}");
        }

        $this->info("\n=== Verification ===");
        $this->info("✓ Customer ID: " . ($answers->first()->customer_id ?? 'N/A'));
        $this->info("✓ Reservation ID: " . ($answers->first()->reservation_id ?? 'N/A'));
        $this->info("✓ Property ID: " . ($answers->first()->property_id ?? 'N/A'));
        $this->info("✓ Submission Date: " . ($answers->first()->created_at ? $answers->first()->created_at->format('Y-m-d H:i:s') : 'N/A'));
        
        $this->info("\n=== View in Admin Panel ===");
        $this->info("Go to: Property Question Form → Select Property → ID {$property->id}");
        $this->info("You should see all {$answers->count()} answers for this reservation.");
    }
}

