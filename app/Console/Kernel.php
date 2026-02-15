<?php

namespace App\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SeedPropertyDataCommand;
use App\Console\Commands\GenerateMonthlyTaxInvoices;
use App\Console\Commands\CheckTodayCheckouts;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SeedPropertyDataCommand::class,
        GenerateMonthlyTaxInvoices::class,
        \App\Console\Commands\SendCheckoutReminders::class,
        \App\Console\Commands\SendFeedbackRequestEmails::class,
        \App\Console\Commands\TestHotelEmailTemplate::class,
        \App\Console\Commands\TestCheckoutReminderEmail::class,
        \App\Console\Commands\ProcessTaxInvoiceQueue::class,
        \App\Console\Commands\BackupTaxInvoiceSender::class,
        \App\Console\Commands\GuaranteedFeedbackRequests::class,
        \App\Console\Commands\GuaranteedCheckoutReminders::class,
        \App\Console\Commands\GuaranteedTaxInvoices::class,
        \App\Console\Commands\TestFlexibleHotelBookingEmail::class,
        CheckTodayCheckouts::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('demo:remove-advertisements')->daily();
        $schedule->command('demo:remove-customers')->daily();
        $schedule->command('demo:remove-chats')->daily();
        $schedule->command('demo:remove-properties')->daily();
        $schedule->command('demo:remove-projects')->daily();

        // Generate and send monthly tax invoices on the 1st of each month at 9 AM
        $schedule->command('tax:generate-monthly-invoices')
            ->monthlyOn(1, '09:00')
            ->appendOutputTo(storage_path('logs/monthly-tax-invoices.log'));

        // Send daily checkout reminders at 9 AM every day
        $schedule->command('reservations:send-checkout-reminders')
            ->dailyAt('09:00')
            ->appendOutputTo(storage_path('logs/checkout-reminders.log'));
        
        // Send feedback request emails at 10 AM every day (after checkout reminders)
        $schedule->command('reservations:send-feedback-requests')
            ->dailyAt('10:00')
            ->appendOutputTo(storage_path('logs/feedback-requests.log'));

        // Generate monthly tax invoices on the 15th of each month at 9:00 AM
        $schedule->command('tax:generate-monthly-invoices')
            ->monthlyOn(15, '09:00')
            ->appendOutputTo(storage_path('logs/tax-invoices.log'));

        // Backup tax invoice sender - runs 2 hours later as fallback
        $schedule->command('tax:backup-send')
            ->monthlyOn(15, '11:00')
            ->appendOutputTo(storage_path('logs/backup-tax-invoices.log'));

        // Process any pending queue entries daily
        $schedule->command('tax:process-queue')
            ->dailyAt('12:00')
            ->appendOutputTo(storage_path('logs/tax-queue.log'));

        // Guaranteed feedback requests - daily at 10:30 AM
        $schedule->command('feedback:guaranteed-send')
            ->dailyAt('10:30')
            ->appendOutputTo(storage_path('logs/guaranteed-feedback.log'));

        // Guaranteed checkout reminders - daily at 9:30 AM
        $schedule->command('checkout:guaranteed-reminders')
            ->dailyAt('09:30')
            ->appendOutputTo(storage_path('logs/guaranteed-checkout.log'));

        // Guaranteed tax invoices - 15th at 9:30 AM (backup to main)
        $schedule->command('tax:guaranteed-invoices')
            ->monthlyOn(15, '09:30')
            ->appendOutputTo(storage_path('logs/guaranteed-tax.log'));
        // Automated monthly flexible invoice emails on the 1st at 10:00 AM
        $schedule->command('invoices:send-flexible')->monthlyOn(1, '10:00');

        // One-off in 10 minutes: Tax invoices (guaranteed)
        $schedule->call(function () {
            $month = Carbon::now()->subMonth()->format('Y-m');
            Artisan::call('tax:guaranteed-invoices', ['month' => $month, '--force' => true]);
        })->everyMinute()->when(function () {
            $target = Cache::get('one_off_tax_target');
            if (!$target) {
                Cache::put('one_off_tax_target', now()->addMinutes(10)->toDateTimeString(), 20);
                return false;
            }
            if (Cache::get('one_off_tax_done')) {
                return false;
            }
            return now()->greaterThanOrEqualTo(Carbon::parse($target));
        })->onSuccess(function () {
            Cache::put('one_off_tax_done', true, 1440);
        });

        // One-off in 10 minutes: Feedback Request Emails (guaranteed)
        $schedule->call(function () {
            Artisan::call('feedback:guaranteed-send');
        })->everyMinute()->when(function () {
            $target = Cache::get('one_off_feedback_target');
            if (!$target) {
                Cache::put('one_off_feedback_target', now()->addMinutes(10)->toDateTimeString(), 20);
                return false;
            }
            if (Cache::get('one_off_feedback_done')) {
                return false;
            }
            return now()->greaterThanOrEqualTo(Carbon::parse($target));
        })->onSuccess(function () {
            Cache::put('one_off_feedback_done', true, 1440);
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        $this->load(__DIR__ . '/Commands/Demo');

        require base_path('routes/console.php');
    }
}
