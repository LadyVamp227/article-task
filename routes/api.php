<?php

use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::apiResource('surveys', SurveyController::class);
