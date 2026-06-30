<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    /**
     * Create a survey together with its nested questions and options.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Survey
    {
        return DB::transaction(function () use ($data): Survey {
            $survey = Survey::create($data);

            $this->syncQuestions($survey, $data['questions'] ?? []);

            return $survey;
        });
    }

    /**
     * Update a survey. When "questions" is present it replaces the existing set.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Survey $survey, array $data): Survey
    {
        return DB::transaction(function () use ($survey, $data): Survey {
            $survey->update($data);

            if (array_key_exists('questions', $data)) {
                $survey->questions()->delete();
                $this->syncQuestions($survey, $data['questions']);
            }

            return $survey;
        });
    }

    /**
     * Create the given questions (and their options) for a survey.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    protected function syncQuestions(Survey $survey, array $questions): void
    {
        foreach (array_values($questions) as $questionData) {
            $options = $questionData['options'] ?? [];
            unset($questionData['options']);

            $question = $survey->questions()->create($questionData);

            foreach ($options as $optionData) {
                $question->options()->create($optionData);
            }
        }
    }
}
