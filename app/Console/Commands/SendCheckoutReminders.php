<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Log;

class SendCheckoutReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:send-checkout-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily checkout reminder emails to customers whose reservations are checking out today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting daily checkout reminders process...');

        try {
            // Create an instance of ReservationController
            $reservationController = new ReservationController(
                app(\App\Services\ReservationService::class),
                app(\App\Services\ApiResponseService::class)
            );

            // Call the sendDailyCheckoutReminders method
            $result = $reservationController->sendDailyCheckoutReminders();

            if ($result['success']) {
                $this->info($result['message']);
                $this->info("Sent: {$result['sent_count']}, Failed: {$result['failed_count']}");

                if (isset($result['errors']) && !empty($result['errors'])) {
                    $this->warn('Errors encountered:');
                    foreach ($result['errors'] as $error) {
                        $this->error($error);
                    }
                }

                return Command::SUCCESS;
            } else {
                $this->error($result['message']);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Failed to process checkout reminders: ' . $e->getMessage());
            Log::error('Checkout reminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
