<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->words(2, true);

        return [
            'question_id' => Question::factory(),
            'label' => Str::ucfirst($label),
            'value' => Str::slug($label),
        ];
    }
}
