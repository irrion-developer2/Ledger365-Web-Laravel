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
        Schema::create('tally_bill_allocations', function (Blueprint $table) {
            $table->bigIncrements('bill_allocation_id');

            $table->unsignedBigInteger('voucher_head_id')->nullable();
            $table->foreign('voucher_head_id')->references('voucher_head_id')->on('tally_voucher_heads')->onDelete('cascade');
       
            $table->string('bill_type',100)->nullable();
            $table->decimal('bill_amount',15,3)->nullable();
            $table->string('year_end',10)->nullable();
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
        Schema::dropIfExists('tally_bill_allocations');
    }
};
