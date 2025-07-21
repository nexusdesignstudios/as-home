<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Property;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all properties with corresponding_day values
        $properties = DB::table('propertys')
            ->whereNotNull('corresponding_day')
            ->select('id', 'corresponding_day')
            ->get();

        // Migrate data from old column to new JSON column
        foreach ($properties as $property) {
            // Convert date string to JSON array with date-time ranges
            $date = $property->corresponding_day;

            // Create a date-time range with default times (full day)
            $jsonData = [
                [
                    'from' => $date . ':00:00AM',
                    'to' => $date . ':11:59PM'
                ]
            ];

            // Update the property with the new JSON data
            DB::table('propertys')
                ->where('id', $property->id)
                ->update(['corresponding_day_json' => json_encode($jsonData)]);
        }

        // Now drop the old column and rename the new one
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'corresponding_day')) {
                $table->dropColumn('corresponding_day');
            }
        });

        // Rename the new column to the original name
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'corresponding_day_json')) {
                $table->renameColumn('corresponding_day_json', 'corresponding_day');
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
        // This migration is not reversible as it transforms data
        // The best we can do is create a new column with the old name
        Schema::table('propertys', function (Blueprint $table) {
            if (!Schema::hasColumn('propertys', 'corresponding_day_old')) {
                $table->date('corresponding_day_old')->nullable()->after('corresponding_day');
            }
        });

        // Try to extract the date from the JSON and store it in the old column format
        $properties = DB::table('propertys')
            ->whereNotNull('corresponding_day')
            ->select('id', 'corresponding_day')
            ->get();

        foreach ($properties as $property) {
            try {
                $jsonData = json_decode($property->corresponding_day, true);
                if (isset($jsonData['date'])) {
                    DB::table('propertys')
                        ->where('id', $property->id)
                        ->update(['corresponding_day_old' => $jsonData['date']]);
                }
            } catch (\Exception $e) {
                // Log error but continue
                Log::error('Error reverting corresponding_day for property ' . $property->id . ': ' . $e->getMessage());
            }
        }
    }
};
