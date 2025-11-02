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
        Schema::table('tura_job_qualification', function (Blueprint $table) {
            $table->string('additional_qualification', 255)->nullable()->change();
            $table->text('additional__qualification_details')->nullable()->change();
            $table->string('division', 50)->nullable()->change();
            $table->string('month_of_passing', 20)->nullable()->change();
            $table->string('general_elective_subjects', 500)->nullable()->change();
            $table->string('honors_specialization', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_qualification', function (Blueprint $table) {
            $table->string('additional_qualification', 255)->nullable(false)->change();
            $table->text('additional__qualification_details')->nullable(false)->change();
            $table->string('division', 50)->nullable(false)->change();
            $table->string('month_of_passing', 20)->nullable(false)->change();
            $table->string('general_elective_subjects', 500)->nullable(false)->change();
            $table->string('honors_specialization', 255)->nullable(false)->change();
        });
    }
};
