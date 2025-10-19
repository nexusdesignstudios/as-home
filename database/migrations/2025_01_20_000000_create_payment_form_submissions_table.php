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
        Schema::create('payment_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->string('card_number_masked'); // Store only last 4 digits for security
            $table->string('expiry_date');
            $table->string('cvv_masked'); // Store masked CVV for reference
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('number_of_guests')->default(1);
            $table->text('special_requests')->nullable();
            $table->string('reservable_type'); // 'property' or 'hotel_room'
            $table->json('reservable_data')->nullable(); // For hotel room details
            $table->string('review_url')->nullable();
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('property_id')->references('id')->on('propertys')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['property_id', 'status']);
            $table->index(['customer_email']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_form_submissions');
    }
};
