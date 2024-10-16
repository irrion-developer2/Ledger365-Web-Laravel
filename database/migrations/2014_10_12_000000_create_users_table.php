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
            $table->id();
            $table->string('name',100);
            $table->string('email',100)->unique();
            $table->string('phone',20)->unique()->nullable();
            $table->boolean('is_phone_verified')->default(false);
            $table->string('otp',50)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('sub_id')->nullable();
            $table->string('role',100)->nullable();
            $table->foreignId('owner_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('tally_connector_id')->nullable();
            $table->enum('status', ['1', '0'])->default('1');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password',100)->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
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
