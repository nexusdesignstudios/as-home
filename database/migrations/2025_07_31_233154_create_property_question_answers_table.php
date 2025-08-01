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
                $table->foreignId('property_id')->constrained('propertys')->onDelete('cascade');
                $table->foreignId('property_question_field_id')->constrained('property_question_fields')->onDelete('cascade');
                $table->text('value');
                $table->timestamps();
                $table->softDeletes();
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
