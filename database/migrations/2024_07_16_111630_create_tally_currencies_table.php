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
        Schema::create('tally_currencies', function (Blueprint $table) {
            $table->increments('currency_id');
            $table->string('currency_guid',100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->integer('alter_id')->nullable();
            $table->string('currency_name', 100);
            $table->string('symbol', 10);
            $table->string('decimal_symbol', 10);
            $table->integer('decimal_places');

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
        Schema::dropIfExists('tally_ledger_currencies');
    }
};
