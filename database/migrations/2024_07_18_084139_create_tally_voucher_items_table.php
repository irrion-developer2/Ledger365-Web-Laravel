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
        Schema::create('tally_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tally_voucher_id')->nullable();
            $table->foreign('tally_voucher_id')->references('id')->on('tally_vouchers')->onDelete('cascade');
            $table->string('stock_item_name')->nullable();
            $table->string('gst_taxability')->nullable();
            $table->string('gst_source_type')->nullable();
            $table->string('gst_item_source')->nullable();
            $table->string('gst_ledger_source')->nullable();
            $table->string('hsn_source_type')->nullable();
            $table->string('hsn_item_source')->nullable();
            $table->string('gst_rate_infer_applicability')->nullable();
            $table->string('gst_hsn_infer_applicability')->nullable();
            $table->string('rate')->nullable();
            $table->string('unit')->nullable();
            $table->string('billed_qty')->nullable();
            $table->string('amount')->nullable();
            $table->string('discount')->nullable();
            $table->string('igst_rate')->nullable();
            $table->string('gst_hsn_name')->nullable();
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
        Schema::dropIfExists('tally_voucher_items');
    }
};
