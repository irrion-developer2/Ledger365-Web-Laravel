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
        Schema::create('tally_voucher_heads', function (Blueprint $table) {
            $table->increments('voucher_head_id');

            $table->unsignedInteger('voucher_id');
            $table->foreign('voucher_id')->references('voucher_id')->on('tally_vouchers')->onDelete('cascade');

            $table->unsignedInteger('ledger_id');
            $table->foreign('ledger_id')->references('ledger_id')->on('tally_ledgers');

            $table->boolean('is_party_ledger')->default(false);
            $table->decimal('amount', 15, 3)->default(0);
            $table->string('entry_type',10);
            $table->boolean('is_deemed_positive')->default(false);

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
        Schema::dropIfExists('tally_voucher_heads');
    }
};
