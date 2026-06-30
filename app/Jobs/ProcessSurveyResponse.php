<?php

namespace App\Jobs;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSurveyResponse implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying after a failure.
     */
    public int $backoff = 5;

    /**
     * Max seconds the job may run before being considered failed.
     */
    public int $timeout = 60;

    public function __construct(
        public readonly int $surveyResponseId,
    ) {}

    /**
     * Expand the response's durable answers_payload into normalized answer rows.
     */
    public function handle(): void
    {
        $response = SurveyResponse::find($this->surveyResponseId);

        // Already processed (or gone) — nothing to do. Cheap check without a lock.
        if ($response === null || $response->processed_at !== null) {
            return;
        }

        $survey = Survey::with('questions')->find($response->survey_id);

        if ($survey === null) {
            return;
        }

        DB::transaction(function () use ($survey): void {
            // Lock the row so concurrent attempts (e.g. a retry racing the
            // reconciliation command) can't both write the same answers.
            $response = SurveyResponse::whereKey($this->surveyResponseId)
                ->lockForUpdate()
                ->first();

            if ($response === null || $response->processed_at !== null) {
                return;
            }

            $this->writeAnswers($response, $survey);

            $response->forceFill(['processed_at' => now()])->save();
        });
    }

    /**
     * Called when the job fails permanently (after exhausting all attempts).
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Survey response processing failed permanently', [
            'survey_response_id' => $this->surveyResponseId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Create the answer rows from the response's stored payload.
     */
    private function writeAnswers(SurveyResponse $response, Survey $survey): void
    {
        $types = $survey->questions->pluck('type', 'id');

        foreach ($response->answers_payload ?? [] as $questionId => $value) {
            $type = $types[$questionId] ?? null;

            if ($type === null || $value === null || $value === '' || $value === []) {
                continue;
            }

            if ($type === 'multiple_choice') {
                foreach ((array) $value as $optionId) {
                    $response->answers()->create([
                        'question_id' => $questionId,
                        'question_option_id' => $optionId,
                    ]);
                }

                continue;
            }

            if ($type === 'single_choice') {
                $response->answers()->create([
                    'question_id' => $questionId,
                    'question_option_id' => $value,
                ]);

                continue;
            }

            $response->answers()->create([
                'question_id' => $questionId,
                'value' => (string) $value,
            ]);
        }
    }
}
