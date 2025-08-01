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
                $table->unsignedBigInteger('property_question_field_id');
                $table->text('value');
                $table->timestamps();
                $table->softDeletes();

                // Add foreign keys with custom shorter names
                $table->foreign('property_id', 'pq_answers_property_id_foreign')
                    ->references('id')
                    ->on('propertys')
                    ->onDelete('cascade');

                $table->foreign('property_question_field_id', 'pq_answers_field_id_foreign')
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
        Schema::dropIfExists('property_question_answers');
    }
};
