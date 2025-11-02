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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('role',['consumer','ceo','editor'])->default('consumer');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('ward_id');
            $table->string('locality');
            $table->date('dob')->nullable()->default(null);
            $table->string('email')->unique();
            $table->enum('verifyemail',['active','inactive'])->default('inactive');
            $table->string('phone_no');
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
