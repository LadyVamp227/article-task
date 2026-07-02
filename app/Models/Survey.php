<?php

namespace App\Models;

use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'type',
        'starts_at',
        'ends_at',
    ];

    /**
     * Whether this survey uses conditional branching (skip logic).
     */
    public function isBranching(): bool
    {
        return $this->type === 'branching';
    }

    /**
     * Event listener for the model which create publicToken before inserting the data into the DB
     */
    protected static function booted(): void
    {
        static::creating(function (Survey $survey): void {
            if (blank($survey->public_token)) {
                $survey->public_token = static::generatePublicToken();
            }
        });
    }

    /**
     * Generate a unique, unguessable token for public share links.
     */
    public static function generatePublicToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * The public, token-based URL people use to answer this survey.
     */
    public function publicUrl(): string
    {
        return route('surveys.respond', $this);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('id');
    }

    /**
     * @return HasMany<SurveyResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Whether the survey is currently open for new responses.
     */
    public function isAcceptingResponses(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isAfter($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isBefore($now)) {
            return false;
        }

        return true;
    }

    /**
     * The response for a respondent token, if any (may be in-progress or completed).
     */
    public function responseFrom(string $respondentToken): ?SurveyResponse
    {
        return $this->responses()
            ->where('respondent_token', $respondentToken)
            ->first();
    }
}
