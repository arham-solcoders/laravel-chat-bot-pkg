<?php

declare(strict_types=1);

namespace DbChatbot\Database\Factories;

use DbChatbot\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'email' => Str::uuid().'@example.net',
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
        ];
    }
}
