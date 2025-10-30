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
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'feedback_token')) {
                $table->string('feedback_token', 100)->nullable()->after('review_url');
            }
            if (!Schema::hasColumn('reservations', 'feedback_email_sent_at')) {
                $table->timestamp('feedback_email_sent_at')->nullable()->after('feedback_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'feedback_token')) {
                $table->dropColumn('feedback_token');
            }
            if (Schema::hasColumn('reservations', 'feedback_email_sent_at')) {
                $table->dropColumn('feedback_email_sent_at');
            }
        });
    }
};
