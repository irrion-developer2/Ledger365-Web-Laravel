<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TallyLicense>
 */
class TallyLicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => null, // You can set this in the seeder or override it in tests
            'license_number' => 'rajivelectronicals',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
