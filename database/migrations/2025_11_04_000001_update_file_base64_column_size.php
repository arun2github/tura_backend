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
        Schema::table('tura_job_documents_upload', function (Blueprint $table) {
            // Change file_base64 column to LONGTEXT to handle large base64 data
            $table->longText('file_base64')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tura_job_documents_upload', function (Blueprint $table) {
            // Revert back to TEXT (though this may cause data truncation)
            $table->text('file_base64')->nullable()->change();
        });
    }
};