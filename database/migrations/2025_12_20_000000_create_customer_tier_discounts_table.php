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
        Schema::create('customer_tier_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('reservable_type'); // 'App\Models\Property' or 'App\Models\HotelRoom'
            $table->integer('tier_milestone'); // 5, 10, 15 for Properties or 10, 15, 20 for Hotels
            $table->boolean('used')->default(false);
            $table->unsignedBigInteger('reservation_id')->nullable(); // Track which reservation used this discount
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            
            // Ensure one discount per milestone per customer per type
            $table->unique(['customer_id', 'reservable_type', 'tier_milestone'], 'unique_tier_discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_tier_discounts');
    }
};

