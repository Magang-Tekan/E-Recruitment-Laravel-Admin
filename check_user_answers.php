<?php

/**
 * Script untuk mengecek user mana yang sudah punya data user answers
 * Jalankan dengan: php check_user_answers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\UserAnswer;
use App\Models\Application;

echo "=== CEK USER YANG PUNYA USER ANSWERS ===\n\n";

// Get all users with user answers
$userIds = UserAnswer::distinct()->pluck('user_id');
$usersWithAnswers = User::whereIn('id', $userIds)->get();

if ($usersWithAnswers->isEmpty()) {
    echo "❌ Tidak ada user yang memiliki user answers.\n\n";
    echo "Untuk membuat data testing, Anda perlu:\n";
    echo "1. Pastikan ada aplikasi dengan status 'psychotest' atau 'interview'\n";
    echo "2. Jalankan: php artisan db:seed --class=UserAnswerSeeder\n\n";
    
    // Check applications
    echo "=== CEK APLIKASI YANG TERSEDIA ===\n";
    $applications = Application::with(['user', 'status'])->get();
    
    if ($applications->isEmpty()) {
        echo "❌ Tidak ada aplikasi sama sekali.\n";
        echo "Jalankan: php artisan db:seed --class=ApplicationSeeder\n";
    } else {
        echo "Aplikasi yang ada:\n";
        foreach ($applications as $app) {
            echo "- User: {$app->user->name} ({$app->user->email}) | Status: {$app->status->code} | ID: {$app->id}\n";
        }
        echo "\n";
        echo "Untuk membuat user answers, ubah status aplikasi ke 'psychotest' atau 'interview',\n";
        echo "kemudian jalankan: php artisan db:seed --class=UserAnswerSeeder\n";
    }
} else {
    echo "✅ User yang memiliki user answers:\n\n";
    
    foreach ($usersWithAnswers as $user) {
        $answers = UserAnswer::where('user_id', $user->id)
            ->with(['question', 'application'])
            ->get();
        
        $multipleChoiceCount = $answers->filter(function($answer) {
            return $answer->question && $answer->question->question_type === 'multiple_choice';
        })->count();
        
        $essayCount = $answers->filter(function($answer) {
            return $answer->question && $answer->question->question_type === 'essay';
        })->count();
        
        $answerCount = UserAnswer::where('user_id', $user->id)->count();
        
        echo "📋 User: {$user->name}\n";
        echo "   Email: {$user->email}\n";
        echo "   Total Answers: {$answerCount}\n";
        echo "   - Multiple Choice: {$multipleChoiceCount}\n";
        echo "   - Essay: {$essayCount}\n";
        
        // Show applications
        $applications = $answers->pluck('application_id')->unique();
        foreach ($applications as $appId) {
            $app = Application::with('vacancyPeriod.vacancy')->find($appId);
            if ($app) {
                echo "   Application ID: {$appId} - {$app->vacancyPeriod->vacancy->title}\n";
            }
        }
        echo "\n";
    }
    
    echo "\n=== DETAIL USER ANSWERS ===\n";
    foreach ($usersWithAnswers as $user) {
        $answers = UserAnswer::where('user_id', $user->id)
            ->with(['question', 'choice', 'application'])
            ->get();
        
        echo "\n👤 {$user->name} ({$user->email}):\n";
        foreach ($answers as $answer) {
            $questionType = $answer->question ? $answer->question->question_type : 'unknown';
            echo "  - Question ID: {$answer->question_id} | Type: {$questionType}\n";
            
            if ($questionType === 'essay') {
                echo "    Answer: " . substr($answer->answer_text ?? 'No answer', 0, 50) . "...\n";
                echo "    Score: " . ($answer->score ?? 'Not scored') . "\n";
            } else {
                echo "    Choice: " . ($answer->choice ? $answer->choice->choice_text : 'No choice') . "\n";
                echo "    Correct: " . ($answer->choice && $answer->choice->is_correct ? 'Yes' : 'No') . "\n";
            }
        }
    }
}

echo "\n=== SELESAI ===\n";

