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
        if (!Schema::hasTable('property_question_fields')) {
            Schema::create('property_question_fields', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('field_type', ['text', 'number', 'radio', 'checkbox', 'textarea', 'file', 'dropdown']);
                $table->integer('property_classification')->comment('1=sell_rent, 2=commercial, 3=new_project, 4=vacation_homes, 5=hotel_booking');
                $table->integer('rank')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('property_question_fields');
    }
};
