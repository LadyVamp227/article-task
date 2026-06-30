<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'closed'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            // When "questions" is present it replaces the full set for the survey
            // (and must contain at least one question).
            'questions' => ['sometimes', 'required', 'array', 'min:1'],
            'questions.*.title' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(Question::TYPES)],
            'questions.*.is_required' => ['boolean'],

            'questions.*.options' => ['sometimes', 'array'],
            'questions.*.options.*.label' => ['required', 'string', 'max:255'],
            'questions.*.options.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'questions.required' => 'Please add at least one question.',
            'questions.min' => 'Please add at least one question.',
        ];
    }
}
