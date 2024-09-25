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
            $table->string('guid')->unique();
            $table->string('company_guid')->nullable();
            $table->string('name')->nullable();
            $table->string('language_name')->nullable();
            $table->string('parent')->nullable();
            $table->string('category')->nullable();
            $table->string('gst_applicable')->nullable();
            $table->string('tax_classification_name')->nullable();
            $table->string('gst_type_of_supply')->nullable();
            $table->string('excise_applicability')->nullable();
            $table->string('sales_tax_cess_applicable')->nullable();
            $table->string('vat_applicable')->nullable();
            $table->string('costing_method')->nullable();
            $table->string('valuation_method')->nullable();
            $table->string('base_units')->nullable();
            $table->string('additional_units')->nullable();
            $table->string('excise_item_classification')->nullable();
            $table->string('vat_base_unit')->nullable();
            $table->string('is_cost_centres_on')->nullable();
            $table->string('is_batch_wise_on')->nullable();
            $table->string('is_perishable_on')->nullable();
            $table->string('is_entry_tax_applicable')->nullable();
            $table->string('is_cost_tracking_on')->nullable();
            $table->string('is_updating_target_id')->nullable();
            $table->string('is_deleted')->nullable();
            $table->string('is_security_on_when_entered')->nullable();
            $table->string('as_original')->nullable();
            $table->string('is_rate_inclusive_vat')->nullable();
            $table->string('ignore_physical_difference')->nullable();
            $table->string('ignore_negative_stock')->nullable();
            $table->string('treat_sales_as_manufactured')->nullable();
            $table->string('treat_purchases_as_consumed')->nullable();
            $table->string('treat_rejects_as_scrap')->nullable();
            $table->string('has_mfg_date')->nullable();
            $table->string('allow_use_of_expired_items')->nullable();
            $table->string('ignore_batches')->nullable();
            $table->string('ignore_godowns')->nullable();
            $table->string('adj_diff_in_first_sale_ledger')->nullable();
            $table->string('adj_diff_in_first_purc_ledger')->nullable();
            $table->string('cal_con_mrp')->nullable();
            $table->string('exclude_jrnl_for_valuation')->nullable();
            $table->string('is_mrp_incl_of_tax')->nullable();
            $table->string('is_addl_tax_exempt')->nullable();
            $table->string('is_supplementry_duty_on')->nullable();
            $table->string('gvat_is_excise_appl')->nullable();
            $table->string('is_additional_tax')->nullable();
            $table->string('is_cess_exempted')->nullable();
            $table->string('reorder_as_higher')->nullable();
            $table->string('min_order_as_higher')->nullable();
            $table->string('is_excise_calculate_on_mrp')->nullable();
            $table->string('inclusive_tax')->nullable();
            $table->string('gst_calc_slab_on_mrp')->nullable();
            $table->string('modify_mrp_rate')->nullable();
            $table->integer('alter_id')->nullable();
            $table->string('denominator')->nullable();
            $table->string('basic_rate_of_excise', 8, 2)->nullable();
            $table->string('rate_of_vat', 8, 2)->nullable();
            $table->string('vat_base_no')->nullable();
            $table->string('vat_trail_no')->nullable();
            $table->string('vat_actual_ratio', 8, 2)->nullable();
            $table->string('opening_balance')->nullable();
            $table->string('opening_value', 15, 2)->nullable();
            $table->string('opening_rate')->nullable();
            $table->string('unit')->nullable();
            $table->string('igst_rate')->nullable();
            $table->string('hsn_code')->nullable();
            $table->json('gst_details')->nullable();
            $table->json('hsn_details')->nullable();
            $table->string('alias')->nullable();
            $table->string('language_id')->nullable();
            $table->json('batch_allocations')->nullable();
            // $table->string('this_year_balance')->nullable();
            // $table->string('prev_year_balance')->nullable();
            // $table->string('this_quarter_balance')->nullable();
            // $table->string('prev_quarter_balance')->nullable();
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
