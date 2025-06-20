<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parameters', function (Blueprint $table) {
            if (!Schema::hasColumn('parameters', 'status')) {
                $table->tinyInteger('status')->default(1)->after('image');
            }

            if (!Schema::hasColumn('parameters', 'is_required')) {
                $table->tinyInteger('is_required')->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parameters', function (Blueprint $table) {
            if (Schema::hasColumn('parameters', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('parameters', 'is_required')) {
                $table->dropColumn('is_required');
            }
        });
    }
};
