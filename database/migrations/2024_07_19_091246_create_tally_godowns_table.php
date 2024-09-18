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
        Schema::create('tally_godowns', function (Blueprint $table) {
            $table->id();
            $table->string('guid')->unique();
            $table->string('parent')->nullable();
            $table->string('job_name')->nullable();
            $table->string('are1_serial_master')->nullable();
            $table->string('are2_serial_master')->nullable();
            $table->string('are3_serial_master')->nullable();
            $table->string('tax_unit_name')->nullable();
            $table->string('is_updating_target_id')->nullable();
            $table->string('is_deleted')->nullable();
            $table->string('is_security_on_when_entered')->nullable();
            $table->string('as_original')->nullable();
            $table->string('has_no_space')->nullable();
            $table->string('has_no_stock')->nullable();
            $table->string('is_external')->nullable();
            $table->string('is_internal')->nullable();
            $table->string('enable_export')->nullable();
            $table->string('is_primary_excise_unit')->nullable();
            $table->string('allow_export_rebate')->nullable();
            $table->string('is_trader_rg_number_on')->nullable();
            $table->integer('alter_id')->nullable();
            $table->string('language_name')->nullable();
            $table->integer('language_id')->nullable();
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
        Schema::dropIfExists('tally_godowns');
    }
};
