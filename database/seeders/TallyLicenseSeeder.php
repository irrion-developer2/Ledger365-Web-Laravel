<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TallyLicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userIds = \App\Models\User::take(3)->pluck('id');

        foreach ($userIds as $userId) {
            \App\Models\TallyLicense::factory()->create([
                'user_id' => $userId,
                'license_number' => 'rajivelectronicals',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
