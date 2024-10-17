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
            
            $table->unsignedBigInteger('voucher_head_id')->nullable();
            $table->foreign('voucher_head_id')->references('id')->on('tally_voucher_heads')->onDelete('cascade');
       
            $table->string('company_guid',100)->nullable();
            $table->foreign('company_guid')->references('guid')->on('tally_companies')->onDelete('cascade');


            $table->unsignedBigInteger('head_ledger_guid')->nullable();
            $table->foreign('head_ledger_guid')->references('id')->on('tally_ledgers')->onDelete('cascade');
       
           
            $table->string('stock_item_name',100)->nullable();
            $table->string('gst_taxability',20)->nullable();
            $table->string('gst_source_type',100)->nullable();
            $table->string('gst_item_source',100)->nullable();
            $table->string('gst_ledger_source',100)->nullable();
            $table->string('hsn_source_type',100)->nullable();
            $table->string('hsn_item_source',100)->nullable();
            $table->string('gst_rate_infer_applicability',100)->nullable();
            $table->string('gst_hsn_infer_applicability',100)->nullable();
            
            $table->decimal('rate', 15, 3)->nullable(); 
            $table->string('unit',50)->nullable();
            $table->decimal('actual_qty', 15, 3)->nullable(); 
            $table->decimal('billed_qty', 15, 3)->nullable(); 
            $table->decimal('amount', 15, 3)->nullable(); 
            $table->decimal('discount', 15, 3)->nullable();
            $table->decimal('igst_rate', 15, 2)->nullable();
            $table->string('gst_hsn_name',100)->nullable();
            
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
