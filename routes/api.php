<?php

use App\Enums\UserRole;
use App\Http\Controllers\CandidateController;
use Illuminate\Support\Facades\Route;

// API Routes for Candidate Profile Management
Route::middleware(['auth', 'verified', 'role:'.UserRole::CANDIDATE->value])
    ->prefix('candidate')
    ->group(function () {
        // Education management routes
        Route::get('/education', [CandidateController::class, 'getEducation'])->name('api.candidate.education.index');
        Route::post('/education', [CandidateController::class, 'storeEducation'])->name('api.candidate.education.store');
        Route::put('/education/{id}', [CandidateController::class, 'updateEducation'])->name('api.candidate.education.update');
        Route::delete('/education/{id}', [CandidateController::class, 'deleteEducation'])->name('api.candidate.education.delete');
    });
