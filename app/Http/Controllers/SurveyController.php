<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SurveyController extends Controller
{
    public function __construct(private readonly SurveyService $surveys) {}

    /**
     * Display a listing of the surveys.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $surveys = Survey::query()
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status'))
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('title', 'ilike', '%'.$request->string('search').'%')
            )
            ->withCount('questions')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SurveyResource::collection($surveys);
    }

    /**
     * Store a newly created survey.
     */
    public function store(StoreSurveyRequest $request): JsonResponse
    {
        $survey = $this->surveys->create($request->validated());

        $survey->load('questions.options')->loadCount('questions');

        return SurveyResource::make($survey)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified survey.
     */
    public function show(Survey $survey): SurveyResource
    {
        $survey->load('questions.options')->loadCount('questions');

        return SurveyResource::make($survey);
    }

    /**
     * Update the specified survey.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): SurveyResource
    {
        $this->surveys->update($survey, $request->validated());

        $survey->load('questions.options')->loadCount('questions');

        return SurveyResource::make($survey);
    }

    /**
     * Remove the specified survey.
     */
    public function destroy(Survey $survey): Response
    {
        $survey->delete();

        return response()->noContent();
    }
}
