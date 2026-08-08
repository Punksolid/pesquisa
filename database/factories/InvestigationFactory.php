<?php

namespace Database\Factories;

use App\Enums\InvestigationStatus;
use App\Models\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investigation>
 */
class InvestigationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'summary' => fake()->paragraph(),
            'status' => InvestigationStatus::Open,
            'created_by' => User::factory(),
        ];
    }
}
