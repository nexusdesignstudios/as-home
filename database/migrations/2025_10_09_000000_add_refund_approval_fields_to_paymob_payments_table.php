<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRefundApprovalFieldsToPaymobPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paymob_payments', function (Blueprint $table) {
            $table->enum('refund_status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'failed'])->nullable()->after('status');
            $table->text('refund_reason')->nullable()->after('refund_status');
            $table->boolean('requires_approval')->default(false)->after('refund_reason');
            $table->unsignedBigInteger('approved_by')->nullable()->after('requires_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->float('refund_amount', 10, 2)->nullable()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paymob_payments', function (Blueprint $table) {
            $table->dropColumn([
                'refund_status',
                'refund_reason',
                'requires_approval',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'refund_amount'
            ]);
        });
    }
}
