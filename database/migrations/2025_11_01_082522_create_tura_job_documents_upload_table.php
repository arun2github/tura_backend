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
        Schema::create('tura_job_documents_upload', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('job_id');
            $table->string('document_type');
            $table->longText('file_base64');
            $table->string('file_name');
            $table->string('file_extension');
            $table->integer('file_size');
            $table->boolean('is_mandatory')->default(false);
            $table->timestamp('uploaded_at');
            $table->timestamp('updated_at');
            
            // Indexes
            $table->index(['user_id', 'job_id']);
            $table->index(['user_id', 'job_id', 'document_type']);
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('tura_job_postings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tura_job_documents_upload');
    }
};
