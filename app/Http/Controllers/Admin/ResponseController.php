<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;

class ResponseController extends Controller
{
    /**
     * Overview of all responses for a survey.
     */
    public function index(Survey $survey): View
    {
        $survey->load('questions.options');

        $total = $survey->responses()->count();

        $responses = $survey->responses()
            ->withCount('answers')
            ->latest('submitted_at')
            ->paginate(20);

        return view('admin.responses.index', [
            'survey' => $survey,
            'responses' => $responses,
            'total' => $total,
            'summary' => $this->buildSummary($survey, $total),
        ]);
    }

    public function show(Survey $survey, SurveyResponse $response): View
    {
        abort_unless($response->survey_id === $survey->id, 404);

        $survey->load('questions.options');
        $response->load('answers.option');

        // Group this response's answers by question id for easy lookup.
        $answersByQuestion = $response->answers->groupBy('question_id');

        return view('admin.responses.show', [
            'survey' => $survey,
            'response' => $response,
            'answersByQuestion' => $answersByQuestion,
        ]);
    }

    /**
     * Build per-question aggregates across all responses.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildSummary(Survey $survey, int $total): array
    {
        $surveyId = $survey->id;

        $optionCounts = Answer::query()
            ->whereHas('question', fn ($q) => $q->where('survey_id', $surveyId))
            ->whereNotNull('question_option_id')
            ->selectRaw('question_option_id, count(*) as c')
            ->groupBy('question_option_id')
            ->pluck('c', 'question_option_id');

        $answeredCounts = Answer::query()
            ->whereHas('question', fn ($q) => $q->where('survey_id', $surveyId))
            ->selectRaw('question_id, count(distinct survey_response_id) as c')
            ->groupBy('question_id')
            ->pluck('c', 'question_id');

        $summary = [];

        foreach ($survey->questions as $question) {
            $answered = (int) ($answeredCounts[$question->id] ?? 0);
            $entry = [
                'type' => $question->type,
                'answered' => $answered,
            ];

            if (in_array($question->type, ['single_choice', 'multiple_choice'], true)) {
                $entry['options'] = $question->options->map(fn ($option) => [
                    'title' => $option->title,
                    'count' => (int) ($optionCounts[$option->id] ?? 0),
                    'percent' => $total > 0 ? round(($optionCounts[$option->id] ?? 0) / $total * 100) : 0,
                ])->all();
            }

            $summary[$question->id] = $entry;
        }

        return $summary;
    }
}
