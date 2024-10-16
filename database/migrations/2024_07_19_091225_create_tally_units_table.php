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
        Schema::create('tally_units', function (Blueprint $table) {
            $table->id();
            $table->string('guid',100)->unique();
            $table->string('name',100)->nullable();
            // $table->string('is_updating_target_id')->nullable();
            $table->enum('is_deleted', ['Yes', 'No'])->default('No'); 
            // $table->string('is_security_on_when_entered')->nullable();
            // $table->string('as_original')->nullable();
            $table->enum('is_gst_excluded', ['Yes', 'No'])->default('No'); 
            $table->enum('is_simple_unit', ['Yes', 'No'])->default('No');
            $table->integer('alter_id')->nullable();
            $table->date('applicable_from')->nullable();
            $table->string('reporting_uqc_name',100)->nullable();
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
        Schema::dropIfExists('tally_units');
    }
};
