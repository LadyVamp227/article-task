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
     * For branching surveys, options reference their target question by a
     * client-provided "key" (since new questions have no id yet). We create
     * everything first, then resolve those keys to real question ids.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    protected function syncQuestions(Survey $survey, array $questions): void
    {
        $keyToId = [];   // client key => created question id
        $links = [];     // [ [QuestionOption, targetKey], ... ]

        foreach (array_values($questions) as $index => $questionData) {
            $options = $questionData['options'] ?? [];
            $clientKey = (string) ($questionData['key'] ?? $index);
            unset($questionData['options'], $questionData['key']);

            $question = $survey->questions()->create($questionData);
            $keyToId[$clientKey] = $question->id;

            foreach ($options as $optionData) {
                $nextKey = $optionData['next_key'] ?? null;
                unset($optionData['next_key']);

                $option = $question->options()->create($optionData);

                if ($nextKey !== null && $nextKey !== '') {
                    $links[] = [$option, (string) $nextKey];
                }
            }
        }

        // Resolve branching targets now that every question has an id.
        foreach ($links as [$option, $targetKey]) {
            if (isset($keyToId[$targetKey])) {
                $option->update(['next_question_id' => $keyToId[$targetKey]]);
            }
        }
    }
}
