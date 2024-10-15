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
        Schema::create('tally_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('super_admin_user_id')->nullable();
            $table->foreign('super_admin_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('license_number',100)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active'); //chnages to code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tally_licenses');
    }
};
