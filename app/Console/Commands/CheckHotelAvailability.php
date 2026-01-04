<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckHotelAvailability extends Command
{
    protected $signature = 'check:hotel-availability {hotelId} {checkIn} {checkOut}';
    protected $description = 'Check hotel availability for specific dates against reservations';

    public function handle()
    {
        $hotelId = $this->argument('hotelId');
        $checkIn = $this->argument('checkIn');
        $checkOut = $this->argument('checkOut');

        $this->info("🔍 Checking Hotel Availability");
        $this->info("================================");
        $this->info("Hotel ID: {$hotelId}");
        $this->info("Dates: {$checkIn} to {$checkOut}");
        $this->info("");

        // Get hotel with rooms
        $hotel = Property::with(['hotelRooms' => function($query) {
            $query->where('status', 1);
        }])->find($hotelId);

        if (!$hotel) {
            $this->error("Hotel not found");
            return 1;
        }

        $this->info("📋 Hotel: {$hotel->title}");
        $this->info("🏨 Total Rooms: " . count($hotel->hotelRooms));
        $this->info("");

        // Show room details
        $this->info("📋 Room Details:");
        foreach ($hotel->hotelRooms as $room) {
            $this->line("  Room {$room->id} (Type: {$room->room_type_id}) - Price: {$room->price_per_night}");
            if ($room->available_dates) {
                $this->line("    Available Dates: " . json_encode($room->available_dates));
            }
        }
        $this->info("");

        // Get reservations for this hotel
        $reservations = Reservation::where('reservable_id', $hotelId)
            ->where('status', 'confirmed')
            ->where(function($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<=', $checkOut)
                      ->where('check_out_date', '>=', $checkIn);
            })
            ->get();

        $this->info("📋 Reservations overlapping with {$checkIn} to {$checkOut}:");
        if ($reservations->isEmpty()) {
            $this->info("  No reservations found");
        } else {
            foreach ($reservations as $res) {
                $this->line("  Reservation {$res->id}:");
                $this->line("    Room ID: {$res->reservable_id}");
                $this->line("    Check-in: {$res->check_in_date}");
                $this->line("    Check-out: {$res->check_out_date}");
                $this->line("    Status: {$res->status}");
                if ($res->reservable_data) {
                    $this->line("    Reservable Data: " . $res->reservable_data);
                }
                $this->line("");
            }
        }

        // Check availability for each day
        $this->info("📅 Day-by-Day Availability:");
        $current = new \DateTime($checkIn);
        $end = new \DateTime($checkOut);
        
        while ($current < $end) {
            $dateStr = $current->format('Y-m-d');
            $this->line("  📅 {$dateStr}:");
            
            $availableRooms = 0;
            $totalRooms = count($hotel->hotelRooms);
            
            foreach ($hotel->hotelRooms as $room) {
                $isAvailable = $this->isRoomAvailableForDate($room, $current, $reservations);
                if ($isAvailable) {
                    $availableRooms++;
                }
                
                $status = $isAvailable ? "✅ Available" : "❌ Booked";
                $this->line("    Room {$room->id}: {$status}");
            }
            
            $this->line("    Summary: {$availableRooms}/{$totalRooms} rooms available");
            $this->line("");
            
            $current->modify('+1 day');
        }

        return 0;
    }

    private function isRoomAvailableForDate($room, $date, $reservations)
    {
        $dateStr = $date->format('Y-m-d');
        
        // Check available_dates
        if ($room->available_dates && !empty($room->available_dates)) {
            $dates = json_decode($room->available_dates, true);
            if (is_array($dates)) {
                $isAvailableByDates = false;
                foreach ($dates as $range) {
                    if ($dateStr >= $range['from'] && $dateStr <= $range['to'] && $range['type'] !== 'reserved') {
                        $isAvailableByDates = true;
                        break;
                    }
                }
                if (!$isAvailableByDates) {
                    return false;
                }
            }
        }
        
        // Check reservations
        foreach ($reservations as $res) {
            $reservableData = json_decode($res->reservable_data, true);
            $roomMatches = false;
            
            // Check if reservation is for this specific room
            if ($res->reservable_id == $room->id) {
                $roomMatches = true;
            }
            // Check if reservation uses reservable_data array
            elseif (is_array($reservableData)) {
                foreach ($reservableData as $rd) {
                    if (isset($rd['id']) && $rd['id'] == $room->id) {
                        $roomMatches = true;
                        break;
                    }
                }
            }
            
            if ($roomMatches) {
                $checkIn = new \DateTime($res->check_in_date);
                $checkOut = new \DateTime($res->check_out_date);
                $current = new \DateTime($dateStr);
                
                if ($current >= $checkIn && $current < $checkOut) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
