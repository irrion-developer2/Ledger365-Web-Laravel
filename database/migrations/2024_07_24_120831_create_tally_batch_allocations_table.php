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
        Schema::create('tally_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id') // Define the head_id column and set it as a foreign key
            ->constrained('tally_voucher_items')
            ->onDelete('cascade');
            $table->string('batch_name')->nullable();
            $table->string('godown_name')->nullable();
            $table->string('destination_godown_name')->nullable();
            $table->string('amount')->nullable();
            $table->string('actual_qty')->nullable();
            $table->string('billed_qty')->nullable();
            $table->string('order_no')->nullable();
            $table->string('batch_physical_diff')->nullable();
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
        Schema::dropIfExists('tally_batch_allocations');
    }
};
