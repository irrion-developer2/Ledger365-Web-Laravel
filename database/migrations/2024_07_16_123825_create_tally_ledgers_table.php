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
        Schema::create('tally_ledgers', function (Blueprint $table) {
            $table->increments('ledger_id');
            
            $table->string('ledger_guid', 100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('tally_companies');
            
            $table->unsignedInteger('ledger_group_id')->nullable();
            $table->foreign('ledger_group_id')->references('ledger_group_id')->on('tally_ledger_groups');
           
            $table->integer('alter_id')->nullable();
            $table->string('ledger_name', 100);
            $table->string('alias1', 100)->nullable();
            $table->string('alias2', 100)->nullable();
            $table->string('alias3', 100)->nullable();
            $table->string('parent', 100)->nullable()->index();
            $table->string('tax_classification_name', 100)->nullable();
            $table->string('tax_type', 20)->nullable();
            $table->string('bill_credit_period', 50)->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('gst_type', 20)->nullable();
            $table->string('party_gst_in', 20)->nullable();
            $table->string('gst_duty_head', 50)->nullable();
            $table->string('service_category', 20)->nullable();
            $table->string('gst_registration_type', 50)->nullable();
            $table->string('excise_ledger_classification', 20)->nullable();
            $table->string('excise_duty_type', 20)->nullable();
            $table->string('excise_nature_of_purchase', 20)->nullable();
            $table->boolean('is_bill_wise_on')->default(false);
            $table->boolean('is_cost_centres_on')->default(false);
            $table->decimal('opening_balance', 15, 3)->nullable();
            $table->date('applicable_from')->nullable();
            $table->string('ledger_gst_registration_type', 50)->nullable();
            $table->string('gst_in', 20)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->date('mailing_applicable_from')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('mailing_name', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->decimal('this_year_balance', 15, 3)->nullable();
            $table->decimal('prev_year_balance', 15, 3)->nullable();
            $table->decimal('this_quarter_balance', 15, 3)->nullable();
            $table->decimal('prev_quarter_balance', 15, 3)->nullable();
            $table->decimal('on_account_value', 15, 3)->nullable();
            $table->decimal('cash_in_flow', 15, 3)->nullable();
            $table->decimal('cash_out_flow', 15, 3)->nullable();
            $table->decimal('performance', 15, 3)->nullable();

            $table->unique(['company_id', 'ledger_name']);
            
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
        Schema::dropIfExists('tally_ledgers');
    }
};
