<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Add nullable columns - will only be populated for multi-unit vacation homes
            if (!Schema::hasColumn('reservations', 'apartment_id')) {
                $table->unsignedBigInteger('apartment_id')->nullable()->after('property_id');
            }
            if (!Schema::hasColumn('reservations', 'apartment_quantity')) {
                $table->integer('apartment_quantity')->nullable()->after('apartment_id');
            }
        });

        // Add foreign key constraint if apartment_id column was created
        if (Schema::hasColumn('reservations', 'apartment_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                // Check if foreign key doesn't exist before adding
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('reservations');
                
                $hasForeignKey = false;
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getColumns() === ['apartment_id']) {
                        $hasForeignKey = true;
                        break;
                    }
                }
                
                if (!$hasForeignKey) {
                    $table->foreign('apartment_id')->references('id')->on('vacation_apartments')->onDelete('cascade');
                }
            });
        }

        // Add index for performance on multi-unit queries
        Schema::table('reservations', function (Blueprint $table) {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('reservations');
            
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->getName() === 'idx_apartment_dates') {
                    $hasIndex = true;
                    break;
                }
            }
            
            if (!$hasIndex && Schema::hasColumn('reservations', 'apartment_id')) {
                $table->index(['apartment_id', 'check_in_date', 'check_out_date'], 'idx_apartment_dates');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('reservations', 'apartment_id')) {
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('reservations');
                
                foreach ($foreignKeys as $foreignKey) {
                    if (in_array('apartment_id', $foreignKey->getColumns())) {
                        $table->dropForeign([$foreignKey->getName()]);
                        break;
                    }
                }
            }
            
            // Drop index
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('reservations');
            
            foreach ($indexes as $index) {
                if ($index->getName() === 'idx_apartment_dates') {
                    $table->dropIndex('idx_apartment_dates');
                    break;
                }
            }
            
            // Drop columns
            if (Schema::hasColumn('reservations', 'apartment_quantity')) {
                $table->dropColumn('apartment_quantity');
            }
            if (Schema::hasColumn('reservations', 'apartment_id')) {
                $table->dropColumn('apartment_id');
            }
        });
    }
};

