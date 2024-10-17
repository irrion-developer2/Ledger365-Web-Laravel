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
            $table->string('name',100);
            $table->boolean('is_gst_excluded')->default(false);
            $table->boolean('is_simple_unit')->default(false);
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
