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
        Schema::create('tally_companies', function (Blueprint $table) {
            $table->bigIncrements('company_id'); 
            $table->string('guid',100)->unique();
            $table->string('name',100)->index();
            $table->string('state',100)->nullable();
            $table->string('sub_id')->nullable();
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
        Schema::dropIfExists('tally_companies');
    }
};
