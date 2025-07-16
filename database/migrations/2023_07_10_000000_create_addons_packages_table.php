<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddonsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addons_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('property_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_id')->references('id')->on('propertys')->onDelete('cascade');
        });

        // Add package_id to property_hotel_addon_values table
        Schema::table('property_hotel_addon_values', function (Blueprint $table) {
            $table->unsignedBigInteger('package_id')->nullable()->after('multiply_price');
            $table->foreign('package_id')->references('id')->on('addons_packages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_hotel_addon_values', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });

        Schema::dropIfExists('addons_packages');
    }
}
