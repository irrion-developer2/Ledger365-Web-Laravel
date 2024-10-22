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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->bigIncrements('sync_log_id');
            $table->string('serial_number',100)->nullable();
            $table->boolean('is_silver')->default(false);
            $table->boolean('is_gold')->default(false);
            $table->boolean('is_educational_mode')->default(false);
            $table->string('account_id')->nullable();
            $table->boolean('is_indian',100)->default(false);
            $table->string('remote_serial_number',100)->nullable();
            $table->boolean('is_remote_access_mode',100)->default(false);
            $table->boolean('is_lic_client_mode',100)->default(false);
            $table->string('admin_email_id',150)->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('application_path')->nullable();
            $table->string('sv_current_path')->nullable();
            $table->boolean('is_windows')->default(false);
            $table->string('system_name',100)->nullable();
            $table->string('windows_version',100)->nullable();
            $table->string('windows_user',100)->nullable();
            $table->string('ip_address',100)->nullable();
            $table->string('mac_address',100)->nullable();
            $table->string('running_IPV4_addr',100)->nullable();
            $table->string('running_IPV6_addr',100)->nullable();
            $table->boolean('is_os_64')->default(false);
            $table->boolean('tally_in_admin_mode')->default(false);
            $table->string('UAC_status',100)->nullable();
            $table->string('system_proxy_settings',100)->nullable();
            $table->string('module_name',100)->nullable();
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
        Schema::dropIfExists('sync_logs');
    }
};
