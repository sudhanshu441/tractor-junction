<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => (string) fake()->unique()->numberBetween(6000000000, 9999999999),
            'mobile_verified_at' => now(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'user_type' => 'customer',
            'locale' => 'en',
            'is_active' => true,
            'referral_code' => strtoupper(Str::random(8)),
            'remember_token' => Str::random(10),
        ];
    }

    public function staff(): static
    {
        return $this->state(fn () => ['user_type' => 'staff']);
    }

    public function dealer(): static
    {
        return $this->state(fn () => ['user_type' => 'dealer']);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['is_active' => false, 'blocked_at' => now()]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['mobile_verified_at' => null, 'email_verified_at' => null]);
    }
}
