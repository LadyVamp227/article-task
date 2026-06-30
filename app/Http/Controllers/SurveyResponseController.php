<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSurveyResponse;
use App\Models\Survey;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
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
     * Show the public form for answering a survey.
     */
    public function create(Request $request, Survey $survey): View
    {
        if (! $survey->isAcceptingResponses()) {
            return view('surveys.closed', ['survey' => $survey]);
        }

        $token = $this->respondentToken($request);

        if ($survey->hasResponseFrom($token)) {
            return view('surveys.already', ['survey' => $survey]);
        }

        $survey->load('questions.options');

        return view('surveys.respond', ['survey' => $survey]);
    }

    /**
     * Persist a respondent's answers, enforcing one submission per person.
     */
    public function store(Request $request, Survey $survey): RedirectResponse
    {
        if (! $survey->isAcceptingResponses()) {
            return redirect()
                ->route('surveys.respond', $survey)
                ->with('status', 'This survey is no longer accepting responses.');
        }

        $token = $this->respondentToken($request);

        if ($survey->hasResponseFrom($token)) {
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
            // Answers payload for the case when the queue fails.
            $response = $survey->responses()->create([
                'respondent_token' => $token,
                'answers_payload' => $validated['answers'] ?? [],
                'submitted_at' => now(),
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
     * Build dynamic validation rules from the survey's questions.
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
     * Human-friendly attribute names for validation messages.
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
