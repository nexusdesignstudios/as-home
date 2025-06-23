<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add agent_type column (for agent users)
            $table->string('agent_type')->default('not_determined')->comment('individual, company, or not_determined');

            // Add management_type column (for property owner users)
            $table->string('management_type')->default('not_determined')->comment('self, as_home, or not_determined');

            // Update the type column comment to reflect new user types
            DB::statement("ALTER TABLE `users` CHANGE `type` `type` TINYINT COMMENT '0:Admin 1:Agent 2:Property Owner'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('agent_type');
            $table->dropColumn('management_type');

            // Restore original comment
            DB::statement("ALTER TABLE `users` CHANGE `type` `type` TINYINT COMMENT '0:Admin 1:Users'");
        });
    }
};
