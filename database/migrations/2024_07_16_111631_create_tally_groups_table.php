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
        Schema::create('tally_groups', function (Blueprint $table) {
            $table->id();
            $table->string('guid',100)->unique();

            $table->string('company_guid',100)->nullable();
            $table->foreign('company_guid')->references('guid')->on('tally_companies')->onDelete('cascade');
            
            $table->string('name',100)->nullable()->index(); 
            $table->string('parent',100)->nullable();
            $table->enum('affects_stock', ['Yes', 'No'])->default('No');

            $table->integer('alter_id');
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
        Schema::dropIfExists('tally_groups');
    }
};
