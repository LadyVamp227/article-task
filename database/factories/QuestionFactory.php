<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'title' => fake()->sentence().'?',
            'type' => fake()->randomElement(Question::TYPES),
            'is_required' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the question is a single-choice question.
     */
    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'single_choice',
        ]);
    }
}
