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
            $table->bigIncrements('godown_id');
            $table->string('guid',100)->unique();
            $table->string('parent',100)->nullable();
            $table->integer('alter_id')->nullable();
            $table->string('name',100)->nullable();
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
