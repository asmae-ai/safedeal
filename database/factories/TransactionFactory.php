<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id'   => User::factory()->create(['role' => 'vendor'])->id,
            'buyer_id'    => null,
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'amount'      => $this->faker->randomFloat(2, 10, 5000),
            'currency'    => 'MAD',
            'secure_token'=> Str::uuid()->toString(),
            'status'      => TransactionStatus::PendingPayment,
        ];
    }
}