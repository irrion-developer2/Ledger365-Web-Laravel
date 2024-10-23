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
        Schema::create('tally_companies', function (Blueprint $table) {
            $table->increments('company_id'); 
            $table->string('company_guid',100)->unique()->charset('utf8mb4');
            $table->integer('alter_id')->nullable();
            $table->string('company_name',100)->index();
            $table->string('state',100)->nullable();
            $table->string('sub_id')->nullable();
            
            $table->date('starting_from')->nullable();
            $table->string('address')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('address3')->nullable();
            $table->string('address4')->nullable();
            $table->string('address5')->nullable();
            $table->date('books_from')->nullable();
            $table->date('audited_upto')->nullable();
            $table->string('email',100)->nullable();
            $table->string('pincode',10)->nullable();
            $table->string('phone_number',50)->nullable();
            $table->string('mobile_number',50)->nullable();
            $table->string('fax_number',200)->nullable();
            $table->string('website')->nullable();
            $table->string('income_tax_number',10)->nullable();
            $table->integer('company_number')->nullable();
            $table->integer('company_vch_stat_id')->nullable();
            $table->date('this_year_beg')->nullable();
            $table->date('this_year_end')->nullable();
            $table->date('prev_year_beg')->nullable();
            $table->date('prev_year_end')->nullable();
            $table->date('this_quarter_beg')->nullable();
            $table->date('this_quarter_end')->nullable();
            $table->date('prev_quarter_beg')->nullable();
            $table->date('prev_quarter_end')->nullable();

            $table->unique(['company_id', 'company_name']);
            
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
        Schema::dropIfExists('tally_companies');
    }
};
