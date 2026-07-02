<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            // Anonymous, per-browser identity used to prevent duplicate submissions.
            $table->uuid('respondent_token');
            // Durable copy of the submitted answers, written synchronously so the
            // data is never lost even if the queued job fails permanently.
            $table->json('answers_payload')->nullable();
            $table->timestamp('submitted_at');
            // Set when the respondent has finished the whole survey (branching
            // surveys stay in-progress, with answers_payload, until then).
            $table->timestamp('completed_at')->nullable();
            // Set once the answers have been expanded into the `answers` table.
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // One submission per respondent per survey.
            $table->unique(['survey_id', 'respondent_token']);
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
