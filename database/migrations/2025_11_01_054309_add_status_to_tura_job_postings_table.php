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
        Schema::table('tura_job_postings', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->date('application_start_date')->nullable();
            $table->date('application_end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_postings', function (Blueprint $table) {
            $table->dropColumn(['status', 'application_start_date', 'application_end_date']);
        });
    }
};
