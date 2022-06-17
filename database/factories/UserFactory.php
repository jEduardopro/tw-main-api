<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->name();
        return [
            'name' => $name,
            'username' => trim(Str::of($name)->slug('_')->lower()),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'country' => $this->faker->country(),
            'date_birth' => $this->faker->date()
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the model's password should be null.
     *
     * @return static
     */
    public function withoutPassword()
    {
        return $this->state(function (array $attributes) {
            return [
                'password' => null,
            ];
        });
    }

    /**
     * Indicate that the model's password should be null.
     *
     * @return static
     */
    public function withPhoneValidated()
    {
        return $this->state(function (array $attributes) {
            return [
                'country_code' => env('COUNTRY_CODE_TEST'),
                'phone' => env('PHONE_NUMBER_VALIDATED_TEST')
            ];
        });
    }

    /**
     * Indicate that the model's is_activated should be true.
     *
     * @return static
     */
    public function activated()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_activated' => true
            ];
        });
    }
}
