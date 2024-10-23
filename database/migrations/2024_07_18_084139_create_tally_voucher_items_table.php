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
            $table->increments('voucher_item_id');

            $table->unsignedInteger('voucher_head_id');
            $table->foreign('voucher_head_id')->references('voucher_head_id')->on('tally_voucher_heads')->onDelete('cascade');
        
            $table->unsignedInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('tally_items');
       
            $table->unsignedInteger('unit_id')->nullable();
            $table->foreign('unit_id')->references('unit_id')->on('tally_units');
       
            $table->string('gst_taxability',20)->nullable();
            $table->string('gst_source_type',100)->nullable();
            $table->string('gst_item_source',100)->nullable();
            $table->string('gst_ledger_source',100)->nullable();
            $table->string('hsn_source_type',100)->nullable();
            $table->string('hsn_item_source',100)->nullable();
            $table->string('gst_rate_infer_applicability',100)->nullable();
            $table->string('gst_hsn_infer_applicability',100)->nullable();
            
            $table->decimal('rate', 15, 3)->nullable(); 
            
            $table->decimal('actual_qty', 15, 3)->nullable(); 
            $table->decimal('billed_qty', 15, 3)->nullable(); 
            $table->decimal('amount', 15, 3)->nullable(); 
            $table->decimal('discount', 15, 3)->nullable();
            $table->decimal('igst_rate', 5, 2)->nullable();
            $table->string('gst_hsn_name',100)->nullable();
            
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
        Schema::dropIfExists('tally_voucher_items');
    }
};
