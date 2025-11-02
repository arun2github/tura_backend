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
        Schema::create('tura_job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('job_title_department', 255);
            $table->integer('vacancy_count');
            $table->string('category', 50); // UR, OBC, SC, ST etc.
            $table->string('pay_scale', 100);
            $table->text('qualification');
            $table->decimal('fee_general', 8, 2);
            $table->decimal('fee_sc_st', 8, 2);
            $table->decimal('fee_obc', 8, 2)->nullable(); // Adding OBC fee column
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->date('application_start_date')->nullable();
            $table->date('application_end_date')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tura_job_postings');
    }
};
