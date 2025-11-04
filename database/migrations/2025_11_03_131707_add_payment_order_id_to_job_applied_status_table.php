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
        Schema::table('tura_job_applied_status', function (Blueprint $table) {
            $table->string('payment_order_id', 100)->nullable()->after('payment_transaction_id')->comment('SBI ePay Order ID for tracking payments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_applied_status', function (Blueprint $table) {
            $table->dropColumn('payment_order_id');
        });
    }
};
