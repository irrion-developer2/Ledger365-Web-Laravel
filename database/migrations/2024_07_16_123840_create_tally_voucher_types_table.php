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
        Schema::create('tally_voucher_types', function (Blueprint $table) {
            $table->increments('voucher_type_id');
            $table->string('voucher_type_guid',100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->integer('alter_id')->nullable();
            $table->string('voucher_type_name', 100);
            $table->string('parent', 100);
            $table->string('numbering_method', 50);
            $table->boolean('prevent_duplicate')->default(false);
            $table->boolean('use_zero_entries')->default(false);
            $table->boolean('is_deemed_postive');
            $table->boolean('affects_stock');
            $table->boolean('is_active');
            $table->integer('total_vouchers')->nullable();
            $table->integer('cancelled_vouchers')->nullable();
            
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
        Schema::dropIfExists('tally_voucher_types');
    }
};
