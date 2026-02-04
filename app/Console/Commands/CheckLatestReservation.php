<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;

class CheckLatestReservation extends Command
{
    protected $signature = 'check:latest-reservation';
    protected $description = 'Check the latest reservation in the database';

    public function handle()
    {
        $reservation = Reservation::latest()->first();
        if ($reservation) {
            $this->info("Latest Reservation ID: " . $reservation->id);
            $this->info("Created At: " . $reservation->created_at);
        } else {
            $this->error("No reservations found.");
        }
    }
}
