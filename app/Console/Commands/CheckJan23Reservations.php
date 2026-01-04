<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckJan23Reservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:jan23-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check reservations for Jan 23-24 2026';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "=== CHECKING RESERVATIONS FOR JAN 23-24, 2026 ===\n\n";

        $checkIn = '2026-01-23';
        $checkOut = '2026-01-24';

        // First, check what property ID we're using
        $propertySql = "SELECT id, title FROM propertys WHERE title LIKE '%Amazing 4 Star Hotel%' LIMIT 1";
        $property = DB::select($propertySql);
        
        echo "Property found:\n";
        if (!empty($property)) {
            echo "  ID: {$property[0]->id}\n";
            echo "  Title: {$property[0]->title}\n\n";
        } else {
            echo "  NO PROPERTY FOUND!\n\n";
        }

        // Get all reservations for the Amazing 4 Star Hotel
        $sql = "
            SELECT 
                r.id,
                r.reservable_id as room_id,
                r.reservable_type,
                r.check_in_date,
                r.check_out_date,
                r.status,
                r.payment_method,
                r.payment_status,
                r.property_id,
                hr.room_number,
                hrt.name as room_type_name
            FROM reservations r
            LEFT JOIN hotel_rooms hr ON r.reservable_id = hr.id
            LEFT JOIN hotel_room_types hrt ON hr.room_type_id = hrt.id
            WHERE r.reservable_type = 'App\\\\Models\\\\HotelRoom'
            AND r.property_id = (SELECT id FROM propertys WHERE title LIKE '%Amazing 4 Star Hotel%' LIMIT 1)
            AND r.status NOT IN ('cancelled', 'rejected')
            AND (
                (r.check_in_date <= ? AND r.check_out_date > ?) OR
                (r.check_in_date < ? AND r.check_out_date >= ?) OR
                (r.check_in_date >= ? AND r.check_out_date <= ?)
            )
            ORDER BY r.check_in_date
        ";

        $reservations = DB::select($sql, [
            $checkIn, $checkIn,
            $checkOut, $checkOut,
            $checkIn, $checkOut
        ]);

        echo "Found " . count($reservations) . " reservations for Jan 23-24\n\n";

        // Also check ALL reservations for these dates without property filtering
        echo "=== CHECKING ALL RESERVATIONS FOR JAN 23-24 (NO PROPERTY FILTER) ===\n";
        $allSql = "
            SELECT 
                r.id,
                r.reservable_id as room_id,
                r.reservable_type,
                r.check_in_date,
                r.check_out_date,
                r.status,
                r.payment_method,
                r.payment_status,
                r.property_id,
                hr.room_number,
                hrt.name as room_type_name,
                p.title as property_title
            FROM reservations r
            LEFT JOIN hotel_rooms hr ON r.reservable_id = hr.id
            LEFT JOIN hotel_room_types hrt ON hr.room_type_id = hrt.id
            LEFT JOIN propertys p ON r.property_id = p.id
            WHERE r.reservable_type = 'App\\\\Models\\\\HotelRoom'
            AND r.status NOT IN ('cancelled', 'rejected')
            AND (
                (r.check_in_date <= ? AND r.check_out_date > ?) OR
                (r.check_in_date < ? AND r.check_out_date >= ?) OR
                (r.check_in_date >= ? AND r.check_out_date <= ?)
            )
            ORDER BY r.property_id, r.check_in_date
        ";

        $allReservations = DB::select($allSql, [
            $checkIn, $checkIn,
            $checkOut, $checkOut,
            $checkIn, $checkOut
        ]);

        echo "Found " . count($allReservations) . " total reservations for Jan 23-24\n\n";

        foreach ($allReservations as $res) {
            echo "🔒 Reservation ID: {$res->id}\n";
            echo "   Property: {$res->property_title} (ID: {$res->property_id})\n";
            echo "   Room: {$res->room_id} ({$res->room_type_name})\n";
            echo "   Dates: {$res->check_in_date} to {$res->check_out_date}\n";
            echo "   Status: {$res->status}\n";
            echo "   Payment: {$res->payment_method}\n\n";
        }

        if (empty($reservations)) {
            echo "✅ NO RESERVATIONS FOUND - All rooms should be available based on reservations\n";
        } else {
            foreach ($reservations as $res) {
                echo "🔒 Reservation ID: {$res->id}\n";
                echo "   Room: {$res->room_id} ({$res->room_type_name})\n";
                echo "   Dates: {$res->check_in_date} to {$res->check_out_date}\n";
                echo "   Status: {$res->status}\n";
                echo "   Payment: {$res->payment_method}\n";
                
                // Check if this is a blocking reservation
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
                
                echo "   Blocking: " . ($blockingStatus ? 'YES' : 'NO') . "\n";
                echo "   Type: " . ($isFlexible ? 'Flexible' : 'Non-Flexible') . "\n\n";
            }
        }

        echo "\n=== ROOM BY ROOM ANALYSIS ===\n";

        // Check each room individually
        $roomIds = [763, 764, 765, 766, 767, 768, 769]; // Amazing 4 Star Hotel rooms
        
        foreach ($roomIds as $roomId) {
            echo "🏨 Room ID: $roomId\n";
            
            $roomReservations = array_filter($reservations, function($res) use ($roomId) {
                return $res->room_id == $roomId;
            });
            
            echo "   Reservations for this room: " . count($roomReservations) . "\n";
            
            foreach ($roomReservations as $res) {
                echo "   - ID: {$res->id}, Status: {$res->status}, Payment: {$res->payment_method}\n";
            }
            
            if (empty($roomReservations)) {
                echo "   ✅ NO BLOCKING RESERVATIONS\n";
            } else {
                $hasBlocking = false;
                foreach ($roomReservations as $res) {
                    $paymentMethod = $res->payment_method ?: 'cash';
                    $isFlexible = $paymentMethod === 'cash' || $paymentMethod === 'offline';
                    
                    $reservationStatus = strtolower($res->status ?: 'no status');
                    
                    if ($isFlexible) {
                        $blockingStatus = $reservationStatus !== 'cancelled' && $reservationStatus !== 'rejected';
                    } else {
                        $blockingStatuses = ['confirmed', 'approved', 'pending', 'active'];
                        $blockingStatus = in_array($reservationStatus, $blockingStatuses);
                    }
                    
                    if ($blockingStatus) {
                        $hasBlocking = true;
                        echo "   🚫 BLOCKING RESERVATION: ID {$res->id} ({$res->status}, {$res->payment_method})\n";
                        break;
                    }
                }
                
                if (!$hasBlocking) {
                    echo "   ✅ NO BLOCKING RESERVATIONS (all non-blocking)\n";
                }
            }
            
            echo "\n";
        }

        echo "=== ANALYSIS COMPLETE ===\n";

        return 0;
    }
}
