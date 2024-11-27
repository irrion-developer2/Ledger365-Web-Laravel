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
        Schema::create('tally_units', function (Blueprint $table) {
            $table->increments('unit_id');
            $table->string('unit_guid',100)->charset('ascii')->collation('ascii_bin')->unique();

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('tally_companies')->onDelete('cascade');
            
            $table->integer('alter_id')->nullable();
            $table->string('unit_name',100);
            $table->boolean('is_gst_excluded')->default(false);
            $table->boolean('is_simple_unit')->default(false);
            $table->date('applicable_from')->nullable();
            $table->string('reporting_uqc_name',100)->nullable();
            
            $table->unique(['company_id', 'unit_name']);
            
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
        Schema::dropIfExists('tally_units');
    }
};
