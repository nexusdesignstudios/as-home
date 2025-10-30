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
        if (!Schema::hasTable('property_question_answers')) {
            Schema::create('property_question_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('reservation_id')->nullable();
                $table->unsignedBigInteger('property_question_field_id');
                $table->text('value');
                $table->timestamps();
                $table->softDeletes();

                // Add foreign keys with custom shorter names
                $table->foreign('property_id', 'pq_answers_property_id_foreign')
                    ->references('id')
                    ->on('propertys')
                    ->onDelete('cascade');

                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');

                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('reservations')
                    ->onDelete('cascade');

                $table->foreign('property_question_field_id', 'pq_answers_field_id_foreign')
                    ->references('id')
                    ->on('property_question_fields')
                    ->onDelete('cascade');

                // Add index for faster lookups
                $table->index(['customer_id', 'property_id', 'reservation_id'], 'idx_user_property_reservation_review');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_question_answers');
    }
};
