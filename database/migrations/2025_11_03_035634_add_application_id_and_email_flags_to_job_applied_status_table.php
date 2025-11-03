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
            $table->string('application_id', 50)->nullable()->after('job_id')->unique()->index();
            $table->boolean('job_applied_email_sent')->default(false)->after('updated_at');
            $table->boolean('payment_confirmation_email_sent')->default(false)->after('job_applied_email_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_applied_status', function (Blueprint $table) {
            $table->dropColumn(['application_id', 'job_applied_email_sent', 'payment_confirmation_email_sent']);
        });
    }
};
