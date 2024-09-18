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
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();   
            $table->date('transaction_date')->nullable();
            $table->text('narration')->nullable();
            $table->string('chq_ref_no')->nullable();
            $table->string('withdrawl')->nullable();
            $table->string('deposit')->nullable();
            $table->string('balance')->nullable(); 
            $table->string('transaction_type')->nullable();
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
        Schema::dropIfExists('bank_reconciliations');
    }
};
