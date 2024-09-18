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
        Schema::create('tally_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('guid')->unique();
            $table->string('company_guid')->nullable();
            $table->string('voucher_type')->nullable();
            $table->string('is_cancelled')->nullable();
            $table->string('alter_id')->nullable();
            $table->string('party_ledger_name')->nullable();
            $table->string('ledger_guid')->nullable();
            $table->string('voucher_number')->nullable();
            $table->date('voucher_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('reference_date')->nullable();
            $table->string('place_of_supply')->nullable();
            $table->string('country_of_residense')->nullable();
            $table->string('gst_registration_type')->nullable();
            $table->string('narration')->nullable();
            $table->string('order_no')->nullable();
            $table->date('order_date')->nullable();
            $table->string('ship_doc_no')->nullable();
            $table->string('ship_by')->nullable();
            $table->string('final_destination')->nullable();
            $table->string('bill_lading_no')->nullable();
            $table->date('bill_lading_date')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('terms')->nullable();
            $table->string('consignee_name')->nullable();
            $table->string('consignee_state_name')->nullable();
            $table->string('consignee_gstin')->nullable();
            $table->string('consignee_addr')->nullable();
            $table->string('due_date_payment')->nullable();
            $table->string('order_ref')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_addr')->nullable();
            $table->string('delivery_notes')->nullable();
            $table->string('delivery_dates')->nullable();
            $table->string('buyer_gstin')->nullable();
            $table->string('cost_center_name')->nullable();
            $table->string('cost_center_amount')->nullable();
            $table->string('numbering_style')->nullable();
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
        Schema::dropIfExists('tally_vouchers');
    }
};
