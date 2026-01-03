<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckJan23Availability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:check-jan23';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check availability for Jan 23-24 dates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "=== CHECKING JAN 23-24 AVAILABILITY ISSUE ===\n\n";

        // Get hotel rooms and their availability
        $sql = "
            SELECT 
                hr.id,
                hr.room_number,
                hrt.name as room_type_name,
                hr.room_type_id,
                hr.price_per_night
            FROM hotel_rooms hr
            LEFT JOIN hotel_room_types hrt ON hr.room_type_id = hrt.id
            WHERE hr.property_id = (SELECT id FROM propertys WHERE title LIKE '%Amazing 4 Star Hotel%' LIMIT 1)
            ORDER BY hr.room_type_id, hr.id
        ";

        $rooms = DB::select($sql);

        echo "Found " . count($rooms) . " rooms for Amazing 4 Star Hotel\n\n";

        // Check each room for Jan 23-24 availability
        $checkIn = '2026-01-23';
        $checkOut = '2026-01-24';

        echo "Checking availability for dates: $checkIn to $checkOut\n\n";

        $availableRooms = [];
        $unavailableRooms = [];

        foreach ($rooms as $room) {
            echo "=== Room ID: {$room->id} ({$room->room_type_name}) ===\n";
            
            // Get available_dates from the available_dates_hotel_rooms table
            $availableDatesSql = "
                SELECT from_date, to_date, type, price
                FROM available_dates_hotel_rooms 
                WHERE hotel_room_id = ?
                ORDER BY from_date ASC
            ";
            $availableDatesRecords = DB::select($availableDatesSql, [$room->id]);
            
            $availableDates = [];
            foreach ($availableDatesRecords as $record) {
                $availableDates[] = [
                    'from' => $record->from_date,
                    'to' => $record->to_date,
                    'type' => $record->type,
                    'price' => $record->price
                ];
            }
            
            echo "Available dates configured: " . (empty($availableDates) ? 'NO' : 'YES (' . count($availableDates) . ' ranges)') . "\n";
            
            if (!empty($availableDates)) {
                foreach ($availableDates as $i => $dateRange) {
                    echo "  Range " . ($i + 1) . ": {$dateRange['from']} to {$dateRange['to']} ({$dateRange['type']})\n";
                }
            }
            
            // Check if Jan 23 is available in available_dates
            $isAvailableByDates = true;
            if (!empty($availableDates)) {
                $isAvailableByDates = false;
                foreach ($availableDates as $dateRange) {
                    if ($dateRange['type'] !== 'reserved' && 
                        $checkIn >= $dateRange['from'] && 
                        $checkIn <= $dateRange['to']) {
                        $isAvailableByDates = true;
                        break;
                    }
                }
            }
            
            echo "Jan 23 available by dates: " . ($isAvailableByDates ? 'YES' : 'NO') . "\n";
            
            // Check for reservations
            $reservationSql = "
                SELECT 
                    id,
                    check_in_date,
                    check_out_date,
                    status,
                    payment_method,
                    payment_status
                FROM reservations 
                WHERE reservable_type = 'App\\\\Models\\\\HotelRoom'
                AND reservable_id = ?
                AND status NOT IN ('cancelled', 'rejected')
                AND (
                    (check_in_date <= ? AND check_out_date > ?) OR
                    (check_in_date < ? AND check_out_date >= ?) OR
                    (check_in_date >= ? AND check_out_date <= ?)
                )
            ";
            
            $reservations = DB::select($reservationSql, [
                $room->id,
                $checkIn, $checkIn,
                $checkOut, $checkOut,
                $checkIn, $checkOut
            ]);
            
            echo "Active reservations for Jan 23-24: " . count($reservations) . "\n";
            
            if (!empty($reservations)) {
                foreach ($reservations as $res) {
                    echo "  Reservation ID: {$res->id}, Status: {$res->status}, Payment: {$res->payment_method}\n";
                    echo "  Dates: {$res->check_in_date} to {$res->check_out_date}\n";
                }
            }
            
            // Check blocking logic (same as frontend)
            $hasBlockingReservation = false;
            foreach ($reservations as $res) {
                $paymentMethod = $res->payment_method ?: 'cash';
                $isFlexible = $paymentMethod === 'cash' || $paymentMethod === 'offline';
                
                $reservationStatus = strtolower($res->status ?: 'no status');
                
                if ($isFlexible) {
                    // Flexible reservations block unless cancelled/rejected
                    $blockingStatus = $reservationStatus !== 'cancelled' && $reservationStatus !== 'rejected';
                } else {
                    // Non-flexible only block if confirmed/approved/pending
                    $blockingStatuses = ['confirmed', 'approved', 'pending', 'active'];
                    $blockingStatus = in_array($reservationStatus, $blockingStatuses);
                }
                
                if ($blockingStatus) {
                    $hasBlockingReservation = true;
                    echo "  BLOCKING reservation found: ID {$res->id}, Status: {$res->status}, Payment: {$res->payment_method}\n";
                    break;
                }
            }
            
            $isAvailable = $isAvailableByDates && !$hasBlockingReservation;
            
            echo "Final availability: " . ($isAvailable ? '✅ AVAILABLE' : '❌ NOT AVAILABLE') . "\n";
            
            if ($isAvailable) {
                $availableRooms[] = $room;
            } else {
                $unavailableRooms[] = $room;
            }
            
            echo "\n";
        }

        echo "=== SUMMARY ===\n";
        echo "Available rooms: " . count($availableRooms) . "\n";
        echo "Unavailable rooms: " . count($unavailableRooms) . "\n\n";

        echo "Available room types:\n";
        $availableByType = [];
        foreach ($availableRooms as $room) {
            $typeName = $room->room_type_name ?: 'Unknown';
            if (!isset($availableByType[$typeName])) {
                $availableByType[$typeName] = 0;
            }
            $availableByType[$typeName]++;
        }

        foreach ($availableByType as $typeName => $count) {
            echo "  $typeName: $count rooms\n";
        }

        echo "\nUnavailable room types:\n";
        $unavailableByType = [];
        foreach ($unavailableRooms as $room) {
            $typeName = $room->room_type_name ?: 'Unknown';
            if (!isset($unavailableByType[$typeName])) {
                $unavailableByType[$typeName] = 0;
            }
            $unavailableByType[$typeName]++;
        }

        foreach ($unavailableByType as $typeName => $count) {
            echo "  $typeName: $count rooms\n";
        }

        echo "\n=== ANALYSIS COMPLETE ===\n";

        return 0;
    }
}
