<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('propertys', function (Blueprint $table) {
            // Determine which column to use as reference point
            $afterColumn = null;
            if (Schema::hasColumn('propertys', 'non_refundable')) {
                $afterColumn = 'non_refundable';
            } elseif (Schema::hasColumn('propertys', 'refundable')) {
                $afterColumn = 'refundable';
            } elseif (Schema::hasColumn('propertys', 'property_classification')) {
                $afterColumn = 'property_classification';
            }
            
            $addingHotelVat = !Schema::hasColumn('propertys', 'hotel_vat');
            
            if ($addingHotelVat) {
                if ($afterColumn) {
                    $table->string('hotel_vat')->nullable()->after($afterColumn)
                        ->comment('Hotel VAT number for hotel properties');
                } else {
                    $table->string('hotel_vat')->nullable()
                        ->comment('Hotel VAT number for hotel properties');
                }
            }
            
            if (!Schema::hasColumn('propertys', 'hotel_available_rooms')) {
                // If we're adding hotel_vat in this migration, we can reference it
                // Otherwise, check if it exists in the database
                if ($addingHotelVat || Schema::hasColumn('propertys', 'hotel_vat')) {
                    $table->integer('hotel_available_rooms')->nullable()->after('hotel_vat')
                        ->comment('Number of available rooms for hotel properties');
                } elseif ($afterColumn) {
                    $table->integer('hotel_available_rooms')->nullable()->after($afterColumn)
                        ->comment('Number of available rooms for hotel properties');
                } else {
                    $table->integer('hotel_available_rooms')->nullable()
                        ->comment('Number of available rooms for hotel properties');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'hotel_vat')) {
                $table->dropColumn('hotel_vat');
            }
            if (Schema::hasColumn('propertys', 'hotel_available_rooms')) {
                $table->dropColumn('hotel_available_rooms');
            }
        });
    }
};

