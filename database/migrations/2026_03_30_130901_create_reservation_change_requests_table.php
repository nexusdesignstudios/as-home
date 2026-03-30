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
        Schema::create('reservation_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->date('requested_check_in');
            $table->date('requested_check_out');
            $table->decimal('requested_total_price', 15, 2);
            $table->date('old_check_in');
            $table->date('old_check_out');
            $table->decimal('old_total_price', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'waiting_for_payment', 'completed'])->default('pending');
            $table->unsignedBigInteger('requester_id');
            $table->string('requester_type'); // 'guest', 'host', 'admin'
            $table->text('reason')->nullable();
            $table->string('payment_transaction_id')->nullable(); // For extension payments
            $table->timestamp('handheld_at')->nullable(); // timestamp of approval/rejection
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_change_requests');
    }
};
