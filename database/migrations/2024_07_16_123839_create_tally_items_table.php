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
            $table->bigIncrements('item_id');
            $table->string('guid',100)->unique();
            $table->string('company_guid',100);
            $table->foreign('company_guid')->references('guid')->on('tally_companies')->onDelete('cascade');
            $table->string('name',150);

            $table->string('item_group_guid',100)->nullable();
            $table->foreign('item_group_guid')->references('guid')->on('tally_item_groups')->onDelete('cascade');
            
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
            $table->string('additional_units',100)->nullable();
            $table->string('excise_item_classification',100)->nullable();
            $table->string('vat_base_unit',100)->nullable();
            $table->boolean('is_cost_centres_on')->default(false);
            $table->boolean('is_batch_wise_on')->default(false);
            $table->boolean('is_perishable_on')->default(false);
            $table->boolean('is_entry_tax_applicable')->default(false);
            $table->boolean('is_cost_tracking_on')->default(false);
            $table->boolean('is_updating_target_id')->default(false);
            $table->boolean('is_security_on_when_entered')->default(false);
            $table->boolean('as_original')->default(false);
            $table->boolean('is_rate_inclusive_vat')->default(false);
            $table->boolean('ignore_physical_difference')->default(false);
            $table->boolean('ignore_negative_stock')->default(false);
            $table->boolean('treat_sales_as_manufactured')->default(false);
            $table->boolean('treat_purchases_as_consumed')->default(false);
            $table->boolean('treat_rejects_as_scrap')->default(false);
            $table->boolean('has_mfg_date')->default(false);
            $table->boolean('allow_use_of_expired_items')->default(false);
            $table->boolean('ignore_batches')->default(false);
            $table->boolean('ignore_godowns')->default(false);
            $table->boolean('adj_diff_in_first_sale_ledger')->default(false);
            $table->boolean('adj_diff_in_first_purc_ledger')->default(false);
            $table->boolean('cal_con_mrp')->default(false);
            $table->boolean('exclude_jrnl_for_valuation')->default(false);
            $table->boolean('is_mrp_incl_of_tax')->default(false);
            $table->boolean('is_addl_tax_exempt')->default(false);
            $table->boolean('is_supplementry_duty_on')->default(false);
            $table->boolean('gvat_is_excise_appl')->default(false);
            $table->boolean('is_additional_tax')->default(false);
            $table->boolean('is_cess_exempted')->default(false);
            $table->boolean('reorder_as_higher')->default(false);
            $table->boolean('min_order_as_higher')->default(false);
            $table->boolean('is_excise_calculate_on_mrp')->default(false);
            $table->boolean('inclusive_tax')->default(false);
            $table->boolean('gst_calc_slab_on_mrp')->default(false);
            $table->boolean('modify_mrp_rate')->default(false);
            $table->integer('alter_id')->nullable();
            $table->decimal('denominator',10,4)->nullable();
            $table->decimal('basic_rate_of_excise', 8, 2)->nullable();
            $table->decimal('rate_of_vat', 8, 2)->nullable();
            $table->string('vat_base_no',100)->nullable();
            $table->string('vat_trail_no',100)->nullable();
            $table->decimal('vat_actual_ratio', 8, 2)->nullable();

            $table->string('tally_unit_guid',100);
            $table->foreign('tally_unit_guid')->references('guid')->on('tally_units')->onDelete('cascade');
            
            $table->string('base_units',100)->nullable();
            $table->decimal('opening_balance', 15, 3)->nullable();
            $table->decimal('opening_value', 15, 3)->nullable();
            $table->decimal('opening_rate',15,3)->nullable();
            $table->string('unit',100)->nullable();
            $table->decimal('igst_rate',5,2)->nullable();
            $table->string('hsn_code',20)->nullable();
            $table->json('gst_details')->nullable();
            $table->json('hsn_details')->nullable();
            $table->string('alias1',100)->nullable();
            $table->string('alias2',100)->nullable();
            $table->string('alias3',100)->nullable();
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
