<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tweet>
 */
class TweetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            "user_id" => User::factory()->create()->id,
            "body" => $this->faker->text
        ];
    }

    /**
     * Indicate that the model is deleted.
     *
     * @return static
     */
    public function withSoftDelete()
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }
}
