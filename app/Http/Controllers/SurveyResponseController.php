<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSurveyResponse;
use App\Models\Survey;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SurveyResponseController extends Controller
{
    /**
     * Name of the cookie used to identify an anonymous respondent.
     */
    private const string COOKIE = 'respondent_token';

    /**
     * Cookie lifetime in minutes (1 year).
     */
    private const int|float COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * Show the public survey. Linear surveys render every question at once;
     * branching surveys render one question at a time (resuming if in progress).
     */
    public function create(Request $request, Survey $survey): View
    {
        if (! $survey->isAcceptingResponses()) {
            return view('surveys.closed', ['survey' => $survey]);
        }

        $token = $this->respondentToken($request);
        $response = $survey->responseFrom($token);

        if ($response?->completed_at !== null) {
            return view('surveys.already', ['survey' => $survey]);
        }

        if ($survey->isBranching()) {
            $questions = $survey->questions()->with('options')->get();

            if ($questions->isEmpty()) {
                return view('surveys.closed', ['survey' => $survey]);
            }

            return view('surveys.wizard', [
                'survey' => $survey,
                'questions' => $questions->map(fn ($q) => [
                    'id' => $q->id,
                    'title' => $q->title,
                    'type' => $q->type,
                    'required' => (bool) $q->is_required,
                    'options' => $q->options->map(fn ($o) => [
                        'id' => $o->id,
                        'title' => $o->title,
                        'next' => $o->next_question_id,
                    ])->values(),
                ])->values(),
                'startId' => $questions->first()->id,
                'savedAnswers' => (object) ($response?->answers_payload ?? []),
            ]);
        }

        $survey->load('questions.options');

        return view('surveys.respond', ['survey' => $survey]);
    }

    /**
     * Store a full linear submission (all answers at once).
     */
    public function store(Request $request, Survey $survey): RedirectResponse
    {
        // Branching surveys submit one answer at a time via answer().
        if ($survey->isBranching()) {
            return redirect()->route('surveys.respond', $survey);
        }

        if (! $survey->isAcceptingResponses()) {
            return redirect()
                ->route('surveys.respond', $survey)
                ->with('status', 'This survey is no longer accepting responses.');
        }

        $token = $this->respondentToken($request);

        if ($survey->responseFrom($token)?->completed_at !== null) {
            return redirect()
                ->route('surveys.thanks', $survey)
                ->with('status', 'You have already answered this survey.');
        }

        $survey->load('questions.options');

        $validated = $request->validate(
            $this->rulesFor($survey),
            [],
            $this->attributesFor($survey),
        );

        try {
            // Answers saved durably here so nothing is lost if the queue fails.
            $response = $survey->responses()->create([
                'respondent_token' => $token,
                'answers_payload' => $validated['answers'] ?? [],
                'submitted_at' => now(),
                'completed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return redirect()
                ->route('surveys.thanks', $survey)
                ->with('status', 'You have already answered this survey.');
        }

        ProcessSurveyResponse::dispatch($response->id);

        Cookie::queue(self::COOKIE, $token, self::COOKIE_MINUTES);

        return redirect()
            ->route('surveys.thanks', $survey)
            ->with('status', 'Thanks! Your response has been recorded.');
    }

    /**
     * Auto-save a branching survey's answers (called on every change by the
     * wizard) and, when `completed` is true, finalise the submission. Progress
     * is persisted each time so an accidental browser-close can resume.
     */
    public function answer(Request $request, Survey $survey): JsonResponse
    {
        if (! $survey->isBranching() || ! $survey->isAcceptingResponses()) {
            return response()->json(['status' => 'unavailable'], 422);
        }

        $token = $this->respondentToken($request);
        $existing = $survey->responseFrom($token);

        if ($existing?->completed_at !== null) {
            return response()->json(['status' => 'completed']);
        }

        $data = $request->validate([
            'answers' => ['array'],
            'completed' => ['boolean'],
        ]);

        $answers = $this->sanitizeBranchingAnswers($survey, $data['answers'] ?? []);
        $completed = (bool) ($data['completed'] ?? false);

        $response = $survey->responses()->updateOrCreate(
            ['respondent_token' => $token],
            [
                'answers_payload' => $answers,
                'submitted_at' => $existing->submitted_at ?? now(),
                'completed_at' => $completed ? now() : null,
            ],
        );

        Cookie::queue(self::COOKIE, $token, self::COOKIE_MINUTES);

        if ($completed) {
            ProcessSurveyResponse::dispatch($response->id);
        }

        return response()->json(['status' => $completed ? 'completed' : 'saved']);
    }

    /**
     * Show the thank-you page after submitting.
     */
    public function thanks(Survey $survey): View
    {
        return view('surveys.thanks', ['survey' => $survey]);
    }

    private function respondentToken(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || ! Str::isUuid($token)) {
            $token = (string) Str::uuid();
            Cookie::queue(self::COOKIE, $token, self::COOKIE_MINUTES);
        }

        return $token;
    }

    /**
     * Keep only valid answers (option ids that belong to their question, and
     * capped text) so a tampered auto-save payload can't inject bad data.
     *
     * @param  array<int|string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function sanitizeBranchingAnswers(Survey $survey, array $answers): array
    {
        $questions = $survey->questions()->with('options')->get()->keyBy('id');
        $clean = [];

        foreach ($answers as $questionId => $value) {
            $question = $questions->get((int) $questionId);

            if ($question === null) {
                continue;
            }

            if ($question->type === 'multiple_choice') {
                $optionIds = $question->options->pluck('id')->all();
                $picked = array_values(array_filter(
                    array_map('intval', (array) $value),
                    fn ($id) => in_array($id, $optionIds, true),
                ));
                if ($picked !== []) {
                    $clean[(string) $questionId] = $picked;
                }
            } elseif ($question->type === 'single_choice') {
                if (in_array((int) $value, $question->options->pluck('id')->all(), true)) {
                    $clean[(string) $questionId] = (int) $value;
                }
            } else {
                $text = is_string($value) ? trim($value) : '';
                if ($text !== '') {
                    $clean[(string) $questionId] = mb_substr($text, 0, 5000);
                }
            }
        }

        return $clean;
    }

    /**
     * Build dynamic validation rules from the survey's questions (linear).
     *
     * @return array<string, mixed>
     */
    private function rulesFor(Survey $survey): array
    {
        $rules = ['answers' => ['array']];

        foreach ($survey->questions as $question) {
            $key = "answers.{$question->id}";
            $optionIds = $question->options->pluck('id')->all();
            $requiredRule = $question->is_required ? 'required' : 'nullable';

            $rules[$key] = match ($question->type) {
                'multiple_choice' => [$requiredRule, 'array'],
                'single_choice' => [$requiredRule, Rule::in($optionIds)],
                default => [$requiredRule, 'string', 'max:5000'], // textarea
            };

            if ($question->type === 'multiple_choice') {
                $rules["{$key}.*"] = [Rule::in($optionIds)];
            }
        }

        return $rules;
    }

    /**
     * Human-friendly attribute names for validation messages (linear).
     *
     * @return array<string, string>
     */
    private function attributesFor(Survey $survey): array
    {
        $attributes = [];

        foreach ($survey->questions as $question) {
            $attributes["answers.{$question->id}"] = '"'.$question->title.'"';
        }

        return $attributes;
    }
}
