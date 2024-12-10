<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number',50)->unique()->nullable();
            $table->string('session_id')->nullable();
            $table->string('otp')->nullable();
            $table->string('attempts',100)->nullable();
            $table->boolean('success')->default(false)->nullable();
            
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_otps');
    }
};
