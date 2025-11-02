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
            $table->string('present_block', 100)->nullable()->after('present_district');
            $table->string('permanent_block', 100)->nullable()->after('permanent_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_personal_details', function (Blueprint $table) {
            $table->dropColumn(['present_block', 'permanent_block']);
        });
    }
};
