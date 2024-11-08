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
        Schema::create('tally_godowns', function (Blueprint $table) {
            $table->increments('godown_id');
            $table->string('godown_guid',100)->charset('ascii')->collation('ascii_bin')->unique();
            $table->integer('alter_id')->nullable();
            $table->string('parent',100)->nullable();
            $table->string('godown_name',100);

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
        Schema::dropIfExists('tally_godowns');
    }
};
