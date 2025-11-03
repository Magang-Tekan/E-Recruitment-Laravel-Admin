<?php

// Simple test script for export functionality
require_once 'vendor/autoload.php';

use App\Models\Application;

// Test if we can find applications in psychological test stage
$application = Application::with([
    'user.candidatesProfile',
    'vacancyPeriod.vacancy.company',
    'vacancyPeriod.period',
    'userAnswers' => function($query) {
        $query->with(['question', 'choice']);
    },
    'history' => function($query) {
        $query->whereHas('status', function($q) {
            $q->where('stage', 'psychological_test');
        })
        ->where('is_active', true)
        ->with(['status', 'reviewer'])
        ->latest();
    }
])->first();

if ($application) {
    echo "Found application ID: " . $application->id . "\n";
    echo "User: " . $application->user->name . "\n";
    echo "User answers count: " . $application->userAnswers->count() . "\n";
    echo "History count: " . $application->history->count() . "\n";
    
    if ($application->history->count() > 0) {
        $currentHistory = $application->history->first();
        echo "Current status: " . $currentHistory->status->code . "\n";
    }
} else {
    echo "No applications found\n";
}