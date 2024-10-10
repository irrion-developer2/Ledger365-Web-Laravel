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
        Schema::table('tally_voucher_items', function (Blueprint $table) {
            
            $table->string('stock_item_guid')->after('company_guid')->nullable();
            $table->foreign('stock_item_guid')->references('guid')->on('tally_items')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tally_voucher_items', function (Blueprint $table) {
            $table->dropForeign(['stock_item_guid']);
            // Then drop the column
            $table->dropColumn('stock_item_guid');  
        });
    }
};
