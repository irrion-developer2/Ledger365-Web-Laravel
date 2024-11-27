<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            \App\Models\User::factory()->create([
                'name' => 'Pallavi Shinde',
                'email' => 'john@example.com',
                'phone' => '9156526284',
                'is_phone_verified' => 1,
                'otp' => '123456',
                'otp_expires_at' => now()->addMinutes(10),
                'role' => 'Owner',
                'owner_employee_id' => null,  // Optional foreign key reference
                'tally_connector_id' => Str::random(10),
                'status' => 'Active',
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // Make sure to hash passwords
                'two_factor_secret' => Str::random(20),
                'two_factor_recovery_codes' => json_encode([Str::random(10), Str::random(10)]),
                'two_factor_confirmed_at' => now(),
                'remember_token' => Str::random(10),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Models\User::factory()->create([
                'name' => 'Salman Sir',
                'email' => 'john.doe@example.com',
                'phone' => '8698276714',
                'is_phone_verified' => 1,
                'otp' => '654321',
                'otp_expires_at' => now()->addMinutes(10),
                'role' => 'Owner',
                'owner_employee_id' => null,
                'tally_connector_id' => Str::random(10),
                'status' => 'Active',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'two_factor_secret' => Str::random(20),
                'two_factor_recovery_codes' => json_encode([Str::random(10), Str::random(10)]),
                'two_factor_confirmed_at' => now(),
                'remember_token' => Str::random(10),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Models\User::factory()->create([
                'name' => 'Mangesh Sir',
                'email' => 'alice.smith@example.com',
                'phone' => '9860264391',
                'is_phone_verified' => 1,
                'otp' => '654321',
                'otp_expires_at' => now()->addMinutes(10),
                'role' => 'Owner',
                'owner_employee_id' => null,
                'tally_connector_id' => Str::random(10),
                'status' => 'Active',
                'email_verified_at' => now(),
                'password' => Hash::make('alicepassword'),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => Str::random(10),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            \App\Models\User::factory()->create([
                'name' => 'Administrative',
                'email' => 'administrative@example.com',
                'phone' => '1234567890',
                'is_phone_verified' => 1,
                'otp' => '654321',
                'otp_expires_at' => now()->addMinutes(10),
                'role' => 'Administrative',
                'owner_employee_id' => null,
                'tally_connector_id' => Str::random(10),
                'status' => 'Active',
                'email_verified_at' => now(),
                'password' => Hash::make('alicepassword'),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => Str::random(10),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
