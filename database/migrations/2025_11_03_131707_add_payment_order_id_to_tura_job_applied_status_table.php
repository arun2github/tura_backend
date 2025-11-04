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
            // Add payment_order_id column for SBI ePay integration
            $table->string('payment_order_id', 100)->nullable()->after('payment_date')->comment('SBI ePay order ID for payment tracking');
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