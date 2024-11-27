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
        Schema::create('tally_ledger_groups', function (Blueprint $table) {
            $table->increments('ledger_group_id');
            
            $table->string('ledger_group_guid', 100)->charset('ascii')->collation('ascii_bin')->unique();
            
            $table->unsignedInteger('company_id')->nullable();
            $table->foreign('company_id')->references('company_id')->on('tally_companies')->onDelete('cascade');
            
            $table->integer('alter_id');
            $table->string('ledger_group_name', 100)->nullable()->index();
            $table->boolean('is_primary')->default(false);
            $table->string('parent', 100)->nullable();
            $table->boolean('affects_stock')->default(false);
            $table->string('primary_group', 100)->nullable();
            
            $table->unique(['company_id', 'ledger_group_name']);
            
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
        Schema::dropIfExists('tally_ledger_groups');
    }
};
