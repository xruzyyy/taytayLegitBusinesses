<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('status')->default(0);
            $table->integer('is_active')->default(0);
            $table->timestamp('account_expiration_date')->nullable()->default(Carbon::now('Asia/Manila')->addMinutes(10)); // Set default value using Carbon
            $table->string('image')->nullable();
            $table->string('profile_image')->nullable(); // Add profile image field
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role_as')->nullable();
            $table->integer('type')->nullable(); // Change default value to null
            $table->rememberToken();
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
