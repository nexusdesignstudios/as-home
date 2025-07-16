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
        if (!Schema::hasTable('addons_packages')) {
            Schema::create('addons_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('property_id');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->decimal('price', 10, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('property_id')->references('id')->on('propertys')->onDelete('cascade');
            });
        }

        // Add package_id to property_hotel_addon_values table if the column doesn't exist
        if (Schema::hasTable('property_hotel_addon_values') && !Schema::hasColumn('property_hotel_addon_values', 'package_id')) {
            Schema::table('property_hotel_addon_values', function (Blueprint $table) {
                $table->unsignedBigInteger('package_id')->nullable()->after('multiply_price');
                $table->foreign('package_id')->references('id')->on('addons_packages')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to do anything in down() as this is a safety migration
    }
};
