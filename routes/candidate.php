<?php

use App\Enums\UserRole;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateTestController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\CVGeneratorController;
use Illuminate\Support\Facades\Route;

// User route
Route::middleware(['auth', 'verified', 'role:'.UserRole::CANDIDATE->value])
    ->prefix('candidate')
    ->name('user.')
    ->group(function () {
        Route::get('/', [CandidateController::class, 'index'])->name('info');
        Route::get('/profile', [CandidateController::class, 'store'])->name('profile');
        Route::prefix('jobs')
            ->name('jobs.')
            ->group(function () {
                Route::get('/', [JobsController::class, 'index'])->name('index');
                Route::post('/{id}/apply', [JobsController::class, 'apply'])->name('apply');
            });
        
        // CV Generator Routes
        Route::get('/cv', [CVGeneratorController::class, 'index'])->name('cv.index');
        Route::prefix('cv')
            ->name('cv.')
            ->group(function () {
                Route::get('/generate', [CVGeneratorController::class, 'generateCV'])->name('generate');
                Route::get('/download/{id?}', [CVGeneratorController::class, 'downloadCV'])->name('download');
                Route::get('/list', [CVGeneratorController::class, 'listCVs'])->name('list');
                Route::delete('/{id}', [CVGeneratorController::class, 'deleteCV'])->name('delete');
            });
        
        // Test Management Routes
        Route::prefix('application')
            ->name('application.')
            ->group(function () {
                Route::get('/{id}/test', [CandidateTestController::class, 'show'])->name('test');
                Route::get('/{id}/status', [CandidateTestController::class, 'status'])->name('status');
                Route::post('/{id}/submit', [CandidateTestController::class, 'submitTest'])->name('submit');
            });
        
        // Test Answer Routes
        Route::prefix('questions')
            ->name('questions.')
            ->group(function () {
                Route::post('/answer', [CandidateTestController::class, 'saveAnswer'])->name('answer');
            });
    });
