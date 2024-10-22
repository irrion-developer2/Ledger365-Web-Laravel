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
            $table->bigIncrements('voucher_head_id');

            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->foreign('voucher_id')->references('voucher_id')->on('tally_vouchers')->onDelete('cascade');
            // $table->string('ledger_name',100)->nullable();
            // $table->string('ledger_guid',100)->nullable()->index();
            $table->unsignedBigInteger('ledger_id')->nullable();
            $table->foreign('ledger_id')->references('ledger_id')->on('tally_ledgers')->onDelete('cascade');
            
            $table->boolean('is_party_ledger')->default(false);  
            $table->decimal('amount', 15, 3)->nullable(); 
            $table->string('entry_type',10)->nullable();
            $table->boolean('is_deemed_positive')->default(false);  //changes by code


            $table->index(['voucher_id', 'ledger_id']);
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
