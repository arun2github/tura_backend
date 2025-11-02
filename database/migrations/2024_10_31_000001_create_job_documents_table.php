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
        Schema::create('tura_job_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('job_id');
            
            // Mandatory Documents with individual columns
            $table->longText('photo_base64')->nullable();
            $table->string('photo_filename')->nullable();
            $table->string('photo_extension')->nullable();
            $table->integer('photo_file_size')->nullable();
            $table->timestamp('photo_uploaded_at')->nullable();
            
            $table->longText('signature_base64')->nullable();
            $table->string('signature_filename')->nullable();
            $table->string('signature_extension')->nullable();
            $table->integer('signature_file_size')->nullable();
            $table->timestamp('signature_uploaded_at')->nullable();
            
            $table->longText('caste_certificate_base64')->nullable();
            $table->string('caste_certificate_filename')->nullable();
            $table->string('caste_certificate_extension')->nullable();
            $table->integer('caste_certificate_file_size')->nullable();
            $table->timestamp('caste_certificate_uploaded_at')->nullable();
            
            $table->longText('proof_of_age_base64')->nullable();
            $table->string('proof_of_age_filename')->nullable();
            $table->string('proof_of_age_extension')->nullable();
            $table->integer('proof_of_age_file_size')->nullable();
            $table->timestamp('proof_of_age_uploaded_at')->nullable();
            
            $table->longText('educational_qualification_base64')->nullable();
            $table->string('educational_qualification_filename')->nullable();
            $table->string('educational_qualification_extension')->nullable();
            $table->integer('educational_qualification_file_size')->nullable();
            $table->timestamp('educational_qualification_uploaded_at')->nullable();
            
            // Optional Documents with individual columns
            $table->longText('pwd_certificate_base64')->nullable();
            $table->string('pwd_certificate_filename')->nullable();
            $table->string('pwd_certificate_extension')->nullable();
            $table->integer('pwd_certificate_file_size')->nullable();
            $table->timestamp('pwd_certificate_uploaded_at')->nullable();
            
            $table->longText('sports_certificate_base64')->nullable();
            $table->string('sports_certificate_filename')->nullable();
            $table->string('sports_certificate_extension')->nullable();
            $table->integer('sports_certificate_file_size')->nullable();
            $table->timestamp('sports_certificate_uploaded_at')->nullable();
            
            $table->longText('experience_certificate_base64')->nullable();
            $table->string('experience_certificate_filename')->nullable();
            $table->string('experience_certificate_extension')->nullable();
            $table->integer('experience_certificate_file_size')->nullable();
            $table->timestamp('experience_certificate_uploaded_at')->nullable();
            
            $table->longText('proof_of_citizenship_base64')->nullable();
            $table->string('proof_of_citizenship_filename')->nullable();
            $table->string('proof_of_citizenship_extension')->nullable();
            $table->integer('proof_of_citizenship_file_size')->nullable();
            $table->timestamp('proof_of_citizenship_uploaded_at')->nullable();
            
            // Application status tracking
            $table->boolean('is_mandatory_complete')->default(false);
            $table->integer('total_documents_uploaded')->default(0);
            $table->timestamp('application_submitted_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'job_id']);
            $table->unique(['user_id', 'job_id']); // One document record per user per job
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Assuming you have a job_postings table
            // $table->foreign('job_id')->references('id')->on('tura_job_postings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tura_job_documents');
    }
};