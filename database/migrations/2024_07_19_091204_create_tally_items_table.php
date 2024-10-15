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
        Schema::create('tally_items', function (Blueprint $table) {
            $table->id();
            $table->string('guid',100)->unique();
            $table->string('company_guid',100)->nullable();
            $table->foreign('company_guid')->references('guid')->on('tally_companies')->onDelete('cascade');
            $table->string('name',150)->nullable();
            $table->string('language_name',150)->nullable();
            $table->string('parent',100)->nullable()->index();
            $table->string('category',100)->nullable();
            $table->string('gst_applicable',100)->nullable();
            $table->string('tax_classification_name',100)->nullable();
            $table->string('gst_type_of_supply',20)->nullable();
            $table->string('excise_applicability',100)->nullable();
            $table->string('sales_tax_cess_applicable',100)->nullable();
            $table->string('vat_applicable',100)->nullable();
            $table->string('costing_method',100)->nullable();
            $table->string('valuation_method',100)->nullable();
            $table->string('base_units',100)->nullable();
            $table->string('additional_units',100)->nullable();
            $table->string('excise_item_classification',100)->nullable();
            $table->string('vat_base_unit',100)->nullable();
            $table->enum('is_cost_centres_on', ['Yes', 'No'])->default('No');
            $table->enum('is_batch_wise_on', ['Yes', 'No'])->default('No');
            $table->enum('is_perishable_on', ['Yes', 'No'])->default('No');
            $table->enum('is_entry_tax_applicable', ['Yes', 'No'])->default('No');
            $table->enum('is_cost_tracking_on', ['Yes', 'No'])->default('No');
            $table->enum('is_updating_target_id', ['Yes', 'No'])->default('No');
            $table->enum('is_deleted', ['Yes', 'No'])->default('No');
            $table->enum('is_security_on_when_entered', ['Yes', 'No'])->default('No');
            $table->enum('as_original', ['Yes', 'No'])->default('No');
            $table->enum('is_rate_inclusive_vat', ['Yes', 'No'])->default('No');
            $table->enum('ignore_physical_difference', ['Yes', 'No'])->default('No');
            $table->enum('ignore_negative_stock', ['Yes', 'No'])->default('No');
            $table->enum('treat_sales_as_manufactured', ['Yes', 'No'])->default('No');
            $table->enum('treat_purchases_as_consumed', ['Yes', 'No'])->default('No');
            $table->enum('treat_rejects_as_scrap', ['Yes', 'No'])->default('No');
            $table->enum('has_mfg_date', ['Yes', 'No'])->default('No');
            $table->enum('allow_use_of_expired_items', ['Yes', 'No'])->default('No');
            $table->enum('ignore_batches', ['Yes', 'No'])->default('No');
            $table->enum('ignore_godowns', ['Yes', 'No'])->default('No');
            $table->enum('adj_diff_in_first_sale_ledger', ['Yes', 'No'])->default('No');
            $table->enum('adj_diff_in_first_purc_ledger', ['Yes', 'No'])->default('No');
            $table->enum('cal_con_mrp', ['Yes', 'No'])->default('No');
            $table->enum('exclude_jrnl_for_valuation', ['Yes', 'No'])->default('No');
            $table->enum('is_mrp_incl_of_tax', ['Yes', 'No'])->default('No');
            $table->enum('is_addl_tax_exempt', ['Yes', 'No'])->default('No');
            $table->enum('is_supplementry_duty_on', ['Yes', 'No'])->default('No');
            $table->enum('gvat_is_excise_appl', ['Yes', 'No'])->default('No');
            $table->enum('is_additional_tax', ['Yes', 'No'])->default('No');
            $table->enum('is_cess_exempted', ['Yes', 'No'])->default('No');
            $table->enum('reorder_as_higher', ['Yes', 'No'])->default('No');
            $table->enum('min_order_as_higher', ['Yes', 'No'])->default('No');
            $table->enum('is_excise_calculate_on_mrp', ['Yes', 'No'])->default('No');
            $table->enum('inclusive_tax', ['Yes', 'No'])->default('No');
            $table->enum('gst_calc_slab_on_mrp', ['Yes', 'No'])->default('No');
            $table->enum('modify_mrp_rate', ['Yes', 'No'])->default('No');
            $table->integer('alter_id')->nullable();
            $table->decimal('denominator',10,4)->nullable();
            $table->decimal('basic_rate_of_excise', 8, 2)->nullable();
            $table->decimal('rate_of_vat', 8, 2)->nullable();
            $table->string('vat_base_no',100)->nullable();
            $table->string('vat_trail_no',100)->nullable();
            $table->decimal('vat_actual_ratio', 8, 2)->nullable();
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('opening_value', 15, 2)->nullable();
            $table->decimal('opening_rate',15,3)->nullable();
            $table->string('unit',100)->nullable();
            $table->decimal('igst_rate',5,2)->nullable();
            $table->string('hsn_code',20)->nullable();
            $table->json('gst_details')->nullable();
            $table->json('hsn_details')->nullable();
            $table->string('alias1',100)->nullable();
            $table->string('alias2',100)->nullable();
            $table->string('alias3',100)->nullable();
            // $table->string('language_id')->nullable();
            $table->json('batch_allocations')->nullable();
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
        Schema::dropIfExists('tally_items');
    }
};
