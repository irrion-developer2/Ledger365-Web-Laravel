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
        Schema::table('tally_items', function (Blueprint $table) {
            
            $table->string('item_group_guid',100)->after('company_guid')->nullable();
            $table->foreign('item_group_guid')->references('guid')->on('tally_item_groups')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tally_items', function (Blueprint $table) {
            $table->dropColumn('item_group_guid');
        });
    }
};
