<?php

/**
 * Script untuk membuat data testing user answers
 * Script ini akan:
 * 1. Mengubah status aplikasi ke 'psychotest'
 * 2. Menjalankan UserAnswerSeeder
 * 
 * Jalankan dengan: php create_test_user_answers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Status;
use App\Models\UserAnswer;

echo "=== MEMBUAT DATA TESTING USER ANSWERS ===\n\n";

// Get psychotest status
$psychotestStatus = Status::where('code', 'psychotest')->first();

if (!$psychotestStatus) {
    echo "❌ Status 'psychotest' tidak ditemukan. Pastikan StatusSeeder sudah dijalankan.\n";
    exit(1);
}

// Get applications in admin_selection
$applications = Application::with(['user', 'vacancyPeriod.vacancy.questionPack'])->get();

if ($applications->isEmpty()) {
    echo "❌ Tidak ada aplikasi. Jalankan: php artisan db:seed --class=ApplicationSeeder\n";
    exit(1);
}

echo "Mengubah status aplikasi ke 'psychotest'...\n";
$updated = 0;

foreach ($applications as $application) {
    // Check if application has question pack
    if (!$application->vacancyPeriod || !$application->vacancyPeriod->vacancy || !$application->vacancyPeriod->vacancy->questionPack) {
        echo "⚠️  Skip: Application ID {$application->id} - Tidak punya question pack\n";
        continue;
    }
    
    // Update status to psychotest
    $application->update(['status_id' => $psychotestStatus->id]);
    
    // Create history
    $application->history()->create([
        'status_id' => $psychotestStatus->id,
        'processed_at' => now(),
        'notes' => 'Moved to psychotest for testing',
        'is_active' => true,
    ]);
    
    echo "✅ Updated: Application ID {$application->id} - User: {$application->user->name}\n";
    $updated++;
}

if ($updated === 0) {
    echo "❌ Tidak ada aplikasi yang bisa diupdate.\n";
    exit(1);
}

echo "\n{$updated} aplikasi berhasil diupdate ke status 'psychotest'\n\n";

// Clear existing user answers
echo "Menghapus user answers yang sudah ada...\n";
UserAnswer::query()->delete();
echo "✅ User answers cleared\n\n";

// Run UserAnswerSeeder using Artisan
echo "Membuat user answers baru...\n";
\Artisan::call('db:seed', ['--class' => 'UserAnswerSeeder']);
echo \Artisan::output();

echo "\n✅ Selesai! Data testing user answers telah dibuat.\n\n";

// Show summary
$usersWithAnswers = UserAnswer::distinct()->pluck('user_id');
$users = \App\Models\User::whereIn('id', $usersWithAnswers)->get();

echo "=== SUMMARY ===\n";
echo "User yang memiliki user answers:\n";
foreach ($users as $user) {
    $answerCount = UserAnswer::where('user_id', $user->id)->count();
    $essayCount = UserAnswer::where('user_id', $user->id)
        ->whereHas('question', function($q) {
            $q->where('question_type', 'essay');
        })->count();
    $mcCount = UserAnswer::where('user_id', $user->id)
        ->whereHas('question', function($q) {
            $q->where('question_type', 'multiple_choice');
        })->count();
    
    echo "- {$user->name} ({$user->email}): {$answerCount} answers ({$mcCount} MC, {$essayCount} Essay)\n";
}

echo "\n=== INFORMASI LOGIN ===\n";
echo "Untuk testing sebagai admin:\n";
echo "- Email: hr@gmail.com\n";
echo "- Password: password\n\n";

echo "User yang bisa di-test:\n";
foreach ($users as $user) {
    echo "- Email: {$user->email} | Password: password\n";
}

echo "\n=== SELESAI ===\n";

