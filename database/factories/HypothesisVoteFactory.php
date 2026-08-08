<?php

namespace Database\Factories;

use App\Models\Hypothesis;
use App\Models\HypothesisVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HypothesisVote>
 */
class HypothesisVoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hypothesis_id' => Hypothesis::factory(),
            'user_id' => User::factory(),
            'confidence' => fake()->numberBetween(1, 5),
        ];
    }
}
