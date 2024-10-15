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
            $table->string('narration')->nullable();
            $table->string('chq_ref_no',100)->nullable();
            $table->decimal('withdrawl',15,3)->nullable();
            $table->decimal('deposit',15,3)->nullable();
            $table->decimal('balance',15,3)->nullable(); 
            $table->string('transaction_type',100)->nullable();
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
