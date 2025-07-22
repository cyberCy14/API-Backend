<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

     protected $model = Company::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company,
            'display_name' => $this->faker->companySuffix,
            'company_logo'=> $this->faker->imageUrl(640, 480, 'business', true),
            'business_type' => $this->faker->randomElement(['Construction', 'Restaurant', 'Manufacturing', 'Retail', 'Agriculture']),
            'telephone_contact_1' => $this->faker->phoneNumber,
            'email_contact_1' => $this->faker->unique()->safeEmail,
            'barangay' => $this->faker->randomElement(['BALUGO', 'BAGACAY', 'BANILAD', 'BATINGUEL', 'PIAPI', 'CANDAWINONAN', 'DARO', 'CALINDAGAN']),
            'city_municipality' => $this->faker->randomElement(['DUMAGUETE CITY']),
            'province' => $this->faker->randomElement(['NEGROS ORIENTAL']),
            'region' => $this->faker->randomElement(['REGION VII']),
            'zipcode' =>$this->faker->randomDigitNotNull,
            'street' => $this->faker->word,
            'business_registration_number' => $this->faker->randomDigitNotNull,
            'tin_number'=> $this->faker->randomDigitNotNull,
        ];
    }
}
