<?php

namespace App\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SeedPropertyDataCommand;
use App\Console\Commands\GenerateMonthlyTaxInvoices;

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
