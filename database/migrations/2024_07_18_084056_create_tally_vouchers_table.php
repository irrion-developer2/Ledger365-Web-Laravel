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
            $table->increments('voucher_id');
            $table->string('voucher_guid',100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('tally_companies')->onDelete('cascade');

            $table->unsignedInteger('voucher_type_id')->nullable();
            $table->foreign('voucher_type_id')->references('voucher_type_id')->on('tally_voucher_types')->onDelete('cascade');

            $table->integer('alter_id')->nullable();

            // $table->string('voucher_type',100)->index();
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_optional')->default(false);
            $table->string('voucher_number',100)->nullable();
            $table->date('voucher_date')->index();
            $table->string('reference_no',100)->nullable();
            $table->date('reference_date')->nullable();
            $table->string('place_of_supply',100)->nullable();
            $table->string('country_of_residense',100)->nullable();
            $table->string('gst_registration_type',100)->nullable();
            $table->string('narration')->nullable();
            $table->string('order_no',100)->nullable();
            $table->date('order_date')->nullable();
            $table->string('ship_doc_no',100)->nullable();
            $table->string('ship_by',100)->nullable();
            $table->string('final_destination',100)->nullable();
            $table->string('bill_lading_no',100)->nullable();
            $table->date('bill_lading_date')->nullable();
            $table->string('vehicle_no',50)->nullable();
            $table->string('terms')->nullable();
            $table->string('consignee_name',100)->nullable();
            $table->string('consignee_state_name',100)->nullable();
            $table->string('consignee_gstin',20)->nullable();
            $table->string('consignee_addr')->nullable();
            $table->string('due_date_payment',100)->nullable();
            $table->string('order_ref',100)->nullable();
            $table->string('buyer_name',100)->nullable();
            $table->string('buyer_addr')->nullable();
            $table->string('delivery_notes',100)->nullable();
            $table->string('delivery_dates',100)->nullable();
            $table->string('buyer_gstin',20)->nullable();
            $table->string('cost_center_name',100)->nullable();
            $table->decimal('cost_center_amount',15,3)->nullable();
            $table->string('numbering_style',100)->nullable();
            $table->string('json_path',255)->nullable();

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
        Schema::dropIfExists('tally_vouchers');
    }
};
