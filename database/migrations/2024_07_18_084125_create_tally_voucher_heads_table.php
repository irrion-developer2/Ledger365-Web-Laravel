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
        Schema::create('tally_voucher_heads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tally_voucher_id')->nullable();
            $table->foreign('tally_voucher_id')->references('id')->on('tally_vouchers')->onDelete('cascade');
            $table->string('ledger_name')->nullable();
            $table->string('ledger_guid')->nullable();
            $table->string('amount')->nullable();
            $table->string('entry_type')->nullable();
            $table->string('isdeemedpositive')->nullable();
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
        Schema::dropIfExists('tally_voucher_heads');
    }
};
