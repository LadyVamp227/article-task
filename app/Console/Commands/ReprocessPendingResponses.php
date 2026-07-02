<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSurveyResponse;
use App\Models\SurveyResponse;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('responses:reprocess {--minutes=5 : Only reprocess responses older than this}')]
#[Description('Re-dispatch processing for survey responses whose answers were never written (safety net for failed/lost queue jobs).')]
class ReprocessPendingResponses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $pending = SurveyResponse::query()
            ->whereNotNull('completed_at')   // only finished responses
            ->whereNull('processed_at')      // that were never normalized
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending responses to reprocess.');

            return self::SUCCESS;
        }

        foreach ($pending as $response) {
            ProcessSurveyResponse::dispatch($response->id);
        }

        $this->info("Re-dispatched processing for {$pending->count()} response(s).");

        return self::SUCCESS;
    }
}
