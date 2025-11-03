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
        Schema::table('tura_job_personal_details', function (Blueprint $table) {
            // Make landmark columns nullable
            $table->string('permanent_landmark')->nullable()->change();
            $table->string('present_landmark')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_personal_details', function (Blueprint $table) {
            // Revert landmark columns to not nullable (with default empty string to avoid issues)
            $table->string('permanent_landmark')->nullable(false)->default('')->change();
            $table->string('present_landmark')->nullable(false)->default('')->change();
        });
    }
};
