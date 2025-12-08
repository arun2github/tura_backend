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
        Schema::create('tura_admit_cards', function (Blueprint $table) {
            $table->id();
            $table->integer('job_applied_status_id');
            $table->integer('job_id');
            $table->integer('user_id');
            $table->string('application_id', 50);
            $table->string('admit_no', 100)->unique();
            $table->string('roll_number', 50);
            $table->string('full_name', 255);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('category', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->date('exam_date');
            $table->time('exam_start_time')->nullable();
            $table->time('exam_end_time')->nullable();
            $table->time('reporting_time')->nullable();
            $table->string('venue_name', 255);
            $table->text('venue_address');
            $table->longText('photo_base64')->nullable();
            $table->string('job_title', 255);
            $table->datetime('pdf_downloaded_at')->nullable();
            $table->enum('status', ['active', 'cancelled', 'reissued'])->default('active');
            $table->datetime('issued_at')->nullable();
            $table->integer('issued_by')->nullable();
            $table->timestamps();
            
            // Add indexes
            $table->index('job_id');
            $table->index('user_id');
            $table->index('admit_no');
            $table->index('roll_number');
            $table->index('exam_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tura_admit_cards');
    }
};
