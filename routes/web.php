<?php

use App\Enums\UserRole;
use App\Http\Controllers\VacanciesController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [VacanciesController::class, 'index'])->name('home');

Route::get('/test-upload', function () {
    return view('test-upload');
});

Route::middleware('auth')->group(function () {
    // Redirect based on role
    Route::middleware(['verified'])->get('/redirect', function () {
        return Auth::user()->role === UserRole::HR
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.info');
    })->name('dashboard');
});

// Company Management Routes (hanya index dan destroy)
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('company-management', [App\Http\Controllers\Admin\CompanyManagementController::class, 'index'])
        ->name('company-management.index');
    Route::delete('company-management/{company}', [App\Http\Controllers\Admin\CompanyManagementController::class, 'destroy'])
        ->name('company-management.destroy');
});

Route::middleware(['auth'])->group(function () {
    // Period management routes
    Route::prefix('dashboard')->name('periods.')->group(function () {
        Route::get('/periods', [PeriodController::class, 'index'])->name('index');
        Route::post('/periods', [PeriodController::class, 'store'])->name('store');
        Route::get('/periods/{period}/edit', [PeriodController::class, 'edit'])->name('edit');
        Route::put('/periods/{period}', [PeriodController::class, 'update'])->name('update');
        Route::delete('/periods/{period}', [PeriodController::class, 'destroy'])->name('destroy');
        
        // Add vacancies list route
        Route::get('/vacancies/list', [VacanciesController::class, 'getVacanciesList'])->name('vacancies.list');
    });

    // Company management routes
    Route::prefix('dashboard')->name('companies.')->group(function () {
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('/create', [CompanyController::class, 'create'])->name('create');
            Route::post('/', [CompanyController::class, 'store'])->name('store');
            Route::get('/{company}', [CompanyController::class, 'show'])->name('show');
            Route::get('/{company}/dashboard', [CompanyController::class, 'dashboard'])->name('dashboard');
            Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
            Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
            Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
            Route::get('/{company}/periods', [CompanyController::class, 'periods'])->name('periods');
            Route::get('/{company}/candidates', [CompanyController::class, 'candidates'])->name('candidates');
            Route::get('/{company}/candidates/export', [CompanyController::class, 'exportCandidates'])->name('candidates.export');
            
            // Company-specific period routes
            Route::prefix('{company}/periods')->name('periods.')->group(function () {
                Route::post('/', [PeriodController::class, 'store'])->name('store');
                Route::put('/{period}', [PeriodController::class, 'update'])->name('update');
                Route::delete('/{period}', [PeriodController::class, 'destroy'])->name('destroy');
            });
        });
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/candidate.php';
require __DIR__ . '/admin.php';
