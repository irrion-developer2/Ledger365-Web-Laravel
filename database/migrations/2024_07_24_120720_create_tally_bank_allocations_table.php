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
        Schema::create('tally_bank_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('head_id')->constrained('tally_voucher_heads')->onDelete('cascade');
            $table->string('bank_name',100)->nullable();
            $table->date('instrument_date')->nullable();
            $table->string('instrument_number',100)->nullable();
            $table->string('transaction_type',50)->nullable();
            $table->date('bank_date')->nullable();
            $table->decimal('amount',15,3)->nullable();
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
        Schema::dropIfExists('tally_bank_allocations');
    }
};
