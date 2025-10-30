<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;

class CheckTodayCheckouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:checkouts-today {--date= : Optional date (Y-m-d), default: today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List reservations that have check_out_date equal to today (or a provided date)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateOption = $this->option('date');
        $targetDate = $dateOption ? Carbon::parse($dateOption)->startOfDay() : Carbon::today();

        $this->info('Checking reservations with checkout date: ' . $targetDate->format('Y-m-d'));

        $reservations = Reservation::with(['customer', 'property'])
            ->whereDate('check_out_date', $targetDate->format('Y-m-d'))
            ->orderBy('id')
            ->get();

        if ($reservations->isEmpty()) {
            $this->warn('No reservations found checking out on ' . $targetDate->format('Y-m-d'));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Check-In', 'Check-Out', 'Status', 'Customer', 'Property', 'Payment', 'Approval'];
        $rows = [];

        foreach ($reservations as $reservation) {
            $rows[] = [
                $reservation->id,
                optional($reservation->check_in_date)->format('Y-m-d') ?? 'N/A',
                optional($reservation->check_out_date)->format('Y-m-d') ?? 'N/A',
                $reservation->status ?? 'N/A',
                optional($reservation->customer)->name ?? ($reservation->customer_name ?? 'N/A'),
                optional($reservation->property)->name ?? 'N/A',
                $reservation->payment_method ?? 'N/A',
                $reservation->approval_status ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);
        $this->info('Total: ' . $reservations->count());

        return Command::SUCCESS;
    }
}


