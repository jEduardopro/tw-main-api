<?php

namespace Database\Factories;

use App\Traits\FlowTrait;
use App\Utils\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flow>
 */
class FlowFactory extends Factory
{
    use FlowTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->randomElement([Task::PASSWORD_RESET_BEGIN]);

        return [
            "name" => $name,
            "token" => $this->generateFlowToken(),
            "payload" => []
        ];
    }
}
