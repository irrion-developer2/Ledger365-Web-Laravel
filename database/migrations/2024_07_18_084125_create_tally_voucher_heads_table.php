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
            $table->string('ledger_name',100)->nullable();
            $table->string('ledger_guid',100)->nullable()->index();
            $table->decimal('amount', 15, 3)->nullable(); 
            $table->string('entry_type',10)->nullable();
            $table->enum('isdeemedpositive', ['Yes', 'No'])->default('No');
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
