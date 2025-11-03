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
            // Add email column after user_id
            if (!Schema::hasColumn('tura_job_personal_details', 'email')) {
                $table->string('email')->after('user_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_personal_details', function (Blueprint $table) {
            if (Schema::hasColumn('tura_job_personal_details', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
