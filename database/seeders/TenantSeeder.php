<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        

        Schema::disableForeignKeyConstraints();
        DB::table('tenants')->truncate();
        DB::table('domains')->truncate();
        Schema::enableForeignKeyConstraints();

        $tenant1 = Tenant::create([
            'id' => 'foo',
        ]);

        $tenant1->domains()->create(['domain' => 'foo.'.parse_url(config('app.url'), PHP_URL_HOST)]);
       
        $tenant2 = Tenant::create([
            'id' => 'bar',
        ]);

        $tenant2->domains()->create(['domain' => 'bar.'.parse_url(config('app.url'), PHP_URL_HOST)]);
    }
}
