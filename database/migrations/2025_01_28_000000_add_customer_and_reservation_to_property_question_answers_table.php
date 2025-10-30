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
        // Only run if table exists and columns don't exist (idempotent migration)
        if (!Schema::hasTable('property_question_answers')) {
            return; // Table doesn't exist, skip this migration (create migration will handle it)
        }

        Schema::table('property_question_answers', function (Blueprint $table) {
            // Check if columns don't exist before adding them (idempotent migration)
            if (!Schema::hasColumn('property_question_answers', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('property_id');
                
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('property_question_answers', 'reservation_id')) {
                $table->unsignedBigInteger('reservation_id')->nullable()->after('customer_id');
                
                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('reservations')
                    ->onDelete('cascade');
            }
        });

        // Add index separately (check if it exists first)
        if (Schema::hasTable('property_question_answers')) {
            try {
                Schema::table('property_question_answers', function (Blueprint $table) {
                    $table->index(['customer_id', 'property_id', 'reservation_id'], 'idx_user_property_reservation_review');
                });
            } catch (\Exception $e) {
                // Index might already exist, ignore error
            }
        }
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
