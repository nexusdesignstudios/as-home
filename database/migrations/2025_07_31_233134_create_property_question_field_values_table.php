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
        if (!Schema::hasTable('property_question_field_values')) {
            Schema::create('property_question_field_values', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_question_field_id');
                $table->text('value');
                $table->timestamps();
                $table->softDeletes();

                // Add foreign key with a custom shorter name
                $table->foreign('property_question_field_id', 'pq_field_values_field_id_foreign')
                    ->references('id')
                    ->on('property_question_fields')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_question_field_values');
    }
};
