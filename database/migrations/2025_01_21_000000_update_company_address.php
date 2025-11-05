<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update company address if it contains the old address
        $oldAddresses = [
            'Red Sea Governorate - North Hurghada - Al Nour and its Extension - Amlak Lands - 779/777',
            'Red Sea Governorate - North Hurghada - Al Nour and its Extension - Amlak Lands - 779/777<br>',
        ];
        
        $newAddress = 'P.O Box 25 – Hurghada, Egypt';
        
        foreach ($oldAddresses as $oldAddress) {
            Setting::where('type', 'company_address')
                ->where('data', 'LIKE', '%' . $oldAddress . '%')
                ->update(['data' => $newAddress]);
        }
        
        // Also set default if not exists
        Setting::updateOrCreate(
            ['type' => 'company_address'],
            ['data' => $newAddress]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert - but not necessary
    }
};


