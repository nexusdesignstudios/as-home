<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;
use App\Models\HotelRoom;

class DiagnoseTemplateAndReservation extends Command
{
    protected $signature = 'diagnose:template-reservation {id=1466}';
    protected $description = 'Diagnose template content and reservation logic';

    public function handle()
    {
        // 1. Check Template
        $template = DB::table('settings')->where('type', 'flexible_hotel_booking_confirmation_mail_template')->value('data');
        $this->info("Checking Template...");
        if (strpos($template, '{room_type}') !== false) {
            $this->info("✅ Template contains '{room_type}' placeholder.");
        } else {
            $this->error("❌ Template DOES NOT contain '{room_type}' placeholder!");
        }
        
        if (strpos($template, 'Room Type:') !== false) {
             $this->info("✅ Template contains 'Room Type:' label.");
        } else {
             $this->error("❌ Template DOES NOT contain 'Room Type:' label!");
        }

        // 2. Check Reservation
        $id = $this->argument('id');
        $reservation = Reservation::find($id);
        
        if (!$reservation) {
            $this->error("Reservation #$id not found.");
            return;
        }

        $this->info("\nChecking Reservation #$id...");
        $this->info("Type: " . $reservation->reservable_type);
        
        $roomType = '';
        if ($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room') {
            $hotelRoom = $reservation->reservable;
            if ($hotelRoom) {
                $this->info("Hotel Room ID: " . $hotelRoom->id);
                $this->info("Custom Room Type: " . $hotelRoom->custom_room_type);
                $this->info("Relation Room Type: " . (optional($hotelRoom->roomType)->name ?? 'Null'));
                
                $roomType = !empty($hotelRoom->custom_room_type) ? $hotelRoom->custom_room_type : (optional($hotelRoom->roomType)->name ?? 'Standard Room');
            } else {
                $this->error("Hotel Room not found.");
            }
        } else {
             $this->info("Property Title: " . ($reservation->property->title ?? 'N/A'));
             $roomType = $reservation->property->title ?? 'Property';
        }
        
        $this->info("Calculated Room Type: '$roomType'");
        
        if (empty($roomType)) {
             $this->warn("⚠️ Room Type is empty! Fallback logic should have handled this.");
        }
    }
}
