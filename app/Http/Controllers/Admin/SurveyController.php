<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\Question;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SurveyController extends Controller
{
    public function __construct(private readonly SurveyService $surveys) {}

    /**
     * List all surveys for management.
     */
    public function index(): View
    {
        $surveys = Survey::query()
            ->withCount(['questions', 'responses'])
            ->latest()
            ->paginate(15);

        return view('admin.surveys.index', ['surveys' => $surveys]);
    }

    /**
     * Show the form for creating a new survey.
     */
    public function create(): View
    {
        return view('admin.surveys.create', [
            'survey' => new Survey(['status' => 'draft', 'type' => 'linear']),
            'questionTypes' => Question::TYPES,
        ]);
    }

    /**
     * Persist a new survey and its questions.
     */
    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $survey = $this->surveys->create($request->validated());

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', "Survey \"{$survey->title}\" created.");
    }

    /**
     * Show the form for editing an existing survey.
     */
    public function edit(Survey $survey): View
    {
        $survey->load('questions.options');

        return view('admin.surveys.edit', [
            'survey' => $survey,
            'questionTypes' => Question::TYPES,
        ]);
    }

    /**
     * Update an existing survey.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $this->surveys->update($survey, $request->validated());

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', "Survey \"{$survey->title}\" updated.");
    }

    /**
     * Delete a survey.
     */
    public function destroy(Survey $survey): RedirectResponse
    {
        $title = $survey->title;
        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', "Survey \"{$title}\" deleted.");
    }
}
