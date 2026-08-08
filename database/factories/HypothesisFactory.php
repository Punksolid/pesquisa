<?php

namespace Database\Factories;

use App\Enums\HypothesisStatus;
use App\Models\Hypothesis;
use App\Models\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hypothesis>
 */
class HypothesisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'investigation_id' => Investigation::factory(),
            'user_id' => User::factory(),
            'statement' => fake()->paragraph(),
            'status' => HypothesisStatus::Proposed,
        ];
    }
}
