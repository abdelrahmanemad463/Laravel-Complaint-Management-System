<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_primary' => fake()->numerify('01#########'),
            'phone_2' => null,
            'phone_3' => null,
            'phone_4' => null,
            'address' => fake()->address(),
        ];
    }
}
