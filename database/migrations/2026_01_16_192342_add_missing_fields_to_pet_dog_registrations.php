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
        Schema::table('pet_dog_registrations', function (Blueprint $table) {
            $table->string('ward_no')->nullable()->after('address');
            $table->string('district')->default('West Garo Hills')->after('ward_no');
            $table->string('pincode')->nullable()->after('district');
            $table->date('registration_date')->nullable()->after('pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pet_dog_registrations', function (Blueprint $table) {
            $table->dropColumn(['ward_no', 'district', 'pincode', 'registration_date']);
        });
    }
};
