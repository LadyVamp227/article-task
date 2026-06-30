<?php

namespace Database\Factories;

use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['draft', 'published', 'closed']),
            'starts_at' => fake()->optional()->dateTimeBetween('-1 week', '+1 week'),
            'ends_at' => fake()->optional()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    /**
     * Indicate that the survey is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }
}
