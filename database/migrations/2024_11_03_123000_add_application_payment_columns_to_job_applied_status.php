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
            // Add columns if they don't exist
            if (!Schema::hasColumn('tura_job_applied_status', 'application_id')) {
                $table->string('application_id', 50)->unique()->after('id');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'email')) {
                $table->string('email')->after('application_id');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'payment_amount')) {
                $table->decimal('payment_amount', 8, 2)->default(0)->after('status');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('payment_amount');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'email_sent')) {
                $table->boolean('email_sent')->default(false)->after('payment_status');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'priority')) {
                $table->integer('priority')->default(1)->after('email_sent');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'category_applied')) {
                $table->string('category_applied', 20)->default('UR')->after('priority');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'payment_transaction_id')) {
                $table->string('payment_transaction_id')->nullable()->after('payment_status');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'payment_date')) {
                $table->timestamp('payment_date')->nullable()->after('payment_transaction_id');
            }
            
            if (!Schema::hasColumn('tura_job_applied_status', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('email_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_applied_status', function (Blueprint $table) {
            $table->dropColumn([
                'application_id',
                'email',
                'payment_amount', 
                'payment_status',
                'email_sent',
                'priority',
                'category_applied',
                'payment_transaction_id',
                'payment_date',
                'email_sent_at'
            ]);
        });
    }
};