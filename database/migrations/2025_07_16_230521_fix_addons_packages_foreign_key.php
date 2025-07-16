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
        // First, drop the existing foreign key constraint
        Schema::table('addons_packages', function (Blueprint $table) {
            // Check if the foreign key exists before attempting to drop it
            if (Schema::hasTable('addons_packages')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('addons_packages');
                $foreignKeyName = null;

                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getLocalColumns() === ['property_id']) {
                        $foreignKeyName = $foreignKey->getName();
                        break;
                    }
                }

                if ($foreignKeyName) {
                    $table->dropForeign($foreignKeyName);
                }
            }
        });

        // Now add the correct foreign key constraint
        Schema::table('addons_packages', function (Blueprint $table) {
            if (Schema::hasTable('addons_packages')) {
                $table->foreign('property_id')->references('id')->on('propertys')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original foreign key constraint
        Schema::table('addons_packages', function (Blueprint $table) {
            if (Schema::hasTable('addons_packages')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('addons_packages');
                $foreignKeyName = null;

                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getLocalColumns() === ['property_id']) {
                        $foreignKeyName = $foreignKey->getName();
                        break;
                    }
                }

                if ($foreignKeyName) {
                    $table->dropForeign($foreignKeyName);
                }

                $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            }
        });
    }
};
