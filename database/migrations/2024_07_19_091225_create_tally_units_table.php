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
            $table->string('guid')->unique();
            $table->string('name')->nullable();
            $table->string('is_updating_target_id')->nullable();
            $table->string('is_deleted')->nullable();
            $table->string('is_security_on_when_entered')->nullable();
            $table->string('as_original')->nullable();
            $table->string('is_gst_excluded')->nullable();
            $table->string('is_simple_unit')->nullable();
            $table->string('alter_id')->nullable();
            $table->date('applicable_from')->nullable();
            $table->string('reporting_uqc_name')->nullable();
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
