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
            $table->string('batch_name',100)->nullable();
            $table->string('godown_name',100)->nullable();
            $table->string('destination_godown_name',100)->nullable();
            $table->decimal('amount',15,3)->nullable();
            $table->decimal('actual_qty',15,3)->nullable();
            $table->decimal('billed_qty',15,3)->nullable();
            $table->string('order_no',100)->nullable();
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
