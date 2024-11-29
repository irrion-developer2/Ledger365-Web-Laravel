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
        Schema::create('tally_item_groups', function (Blueprint $table) {
            $table->increments('item_group_id');
            $table->string('item_group_guid',100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('tally_companies');
            
            $table->integer('alter_id')->nullable();
            $table->string('item_group_name',100)->index(); 
            $table->string('parent',100)->nullable();

            $table->unique(['company_id', 'item_group_name']);
            
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
        Schema::dropIfExists('tally_item_groups');
    }
};
