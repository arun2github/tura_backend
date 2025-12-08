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
        Schema::create('pet_dog_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('application_id')->unique();
            $table->string('owner_name');
            $table->string('identity_proof_type'); // passport, pan, voter_id, aadhar
            $table->string('identity_proof_number');
            $table->string('identity_proof_document')->nullable(); // file path
            $table->string('phone_number');
            $table->string('email');
            $table->string('dog_name');
            $table->string('dog_breed');
            $table->text('address');
            $table->string('vaccination_card_document'); // file path - mandatory
            $table->string('dog_photo_document'); // file path - mandatory
            $table->decimal('registration_fee', 8, 2)->default(50.00);
            $table->decimal('metal_tag_fee', 8, 2)->default(200.00);
            $table->decimal('total_fee', 8, 2)->default(250.00);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('registration_certificate_path')->nullable();
            $table->string('metal_tag_number')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('user_id'); // applicant user
            $table->unsignedBigInteger('approved_by')->nullable(); // admin who approved
            $table->timestamps();
            
            // Index for better performance
            $table->index('application_id');
            $table->index('user_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_dog_registrations');
    }
};
