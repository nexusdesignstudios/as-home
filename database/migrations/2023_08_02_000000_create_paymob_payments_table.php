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
        Schema::create('paymob_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('transaction_id')->nullable();
            $table->string('paymob_order_id')->nullable();
            $table->string('paymob_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('EGP');
            $table->enum('status', ['pending', 'succeed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->text('transaction_data')->nullable();
            $table->text('refund_data')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->string('reservable_type')->nullable(); // App\Models\Property or App\Models\HotelRoom
            $table->unsignedBigInteger('reservable_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paymob_payments');
    }
};
