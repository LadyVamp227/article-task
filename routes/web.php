<?php

use App\Http\Controllers\Admin\ResponseController as AdminResponseController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SurveyResponseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Authentication
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
 * Admin panel (requires authentication)
 */
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/surveys');

    // Reviewing responses.
    Route::get('surveys/{survey}/responses', [AdminResponseController::class, 'index'])
        ->name('surveys.responses.index');
    Route::get('surveys/{survey}/responses/{response}', [AdminResponseController::class, 'show'])
        ->name('surveys.responses.show');

    Route::resource('surveys', AdminSurveyController::class)->except(['show']);
});

/*
 * Public survey answering flow. Surveys are reached only via an unguessable
 * per-survey token, and the routes are rate-limited to deter brute-force
 * enumeration and submission spam.
 */
Route::middleware('throttle:public-survey')->group(function () {
    Route::get('/s/{survey:public_token}', [SurveyResponseController::class, 'create'])
        ->name('surveys.respond');
    Route::post('/s/{survey:public_token}', [SurveyResponseController::class, 'store'])
        ->name('surveys.submit');
    Route::get('/s/{survey:public_token}/thanks', [SurveyResponseController::class, 'thanks'])
        ->name('surveys.thanks');
});
