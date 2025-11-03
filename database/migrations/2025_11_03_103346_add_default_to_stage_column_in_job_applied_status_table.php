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
            // Check if stage column exists and modify it to have a default value
            if (Schema::hasColumn('tura_job_applied_status', 'stage')) {
                $table->integer('stage')->default(0)->change();
            } else {
                // If stage column doesn't exist, create it with default value
                $table->integer('stage')->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_applied_status', function (Blueprint $table) {
            // Remove default value from stage column
            if (Schema::hasColumn('tura_job_applied_status', 'stage')) {
                $table->integer('stage')->nullable(false)->change();
            }
        });
    }
};
