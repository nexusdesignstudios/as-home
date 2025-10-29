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
        Schema::table('property_question_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('property_id');
            $table->unsignedBigInteger('reservation_id')->nullable()->after('customer_id');

            // Add foreign keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->onDelete('cascade');

            // Add index for faster lookups (not unique since one review can have multiple answers/fields)
            $table->index(['customer_id', 'property_id', 'reservation_id'], 'idx_user_property_reservation_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_question_answers', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['reservation_id']);
            $table->dropIndex('idx_user_property_reservation_review');
            $table->dropColumn(['customer_id', 'reservation_id']);
        });
    }
};
