<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\UserAnswer;
use App\Models\Choice;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserAnswerSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing user answers first (use delete instead of truncate to avoid FK issues)
        UserAnswer::query()->delete();
        
        // Get applications that are in assessment or interview stage
        $applications = Application::with([
            'user',
            'vacancyPeriod.vacancy.questionPack.questions.choices'
        ])->whereHas('status', function($query) {
            $query->whereIn('code', ['psychotest', 'interview']);
        })->get();

        if ($applications->isEmpty()) {
            $this->command->info('No applications in assessment/interview stage found.');
            return;
        }

        $this->command->info('Creating user answers for assessment...');

        foreach ($applications as $application) {
            $questionPack = $application->vacancyPeriod->vacancy->questionPack;
            
            if (!$questionPack || $questionPack->questions->isEmpty()) {
                $this->command->info("Skipping application {$application->id} - no question pack or questions");
                continue;
            }

            $this->command->info("Creating answers for user {$application->user->name} for vacancy {$application->vacancyPeriod->vacancy->title}");

            // Create answers for each question in the pack
            foreach ($questionPack->questions as $question) {
                // Handle essay questions
                if ($question->question_type === 'essay') {
                    // Generate sample essay answers based on question content
                    $essayAnswer = '';
                    
                    // Match answer to question content
                    if (stripos($question->question_text, 'tim') !== false || stripos($question->question_text, 'team') !== false) {
                        $essayAnswer = 'Saya memiliki pengalaman bekerja dalam tim selama 3 tahun. Saya selalu berusaha untuk berkomunikasi dengan baik, mendengarkan pendapat anggota tim, dan berkontribusi dengan ide-ide konstruktif. Saya juga membantu menyelesaikan konflik dengan mencari solusi win-win solution.';
                    } elseif (stripos($question->question_text, 'proyek') !== false || stripos($question->question_text, 'project') !== false || stripos($question->question_text, 'menantang') !== false) {
                        $essayAnswer = 'Proyek paling menantang yang pernah saya kerjakan adalah mengembangkan sistem manajemen database untuk perusahaan dengan 10,000+ pengguna. Tantangan utamanya adalah optimasi performa dan skalabilitas. Saya mengatasinya dengan melakukan analisis mendalam, implementasi caching, dan optimasi query database. Proyek ini berhasil meningkatkan performa sistem hingga 300%.';
                    } elseif (stripos($question->question_text, 'motivasi') !== false || stripos($question->question_text, 'motivation') !== false) {
                        $essayAnswer = 'Motivasi utama saya dalam bekerja adalah untuk terus belajar dan berkembang. Saya senang menghadapi tantangan baru dan melihat bagaimana kontribusi saya dapat memberikan dampak positif bagi perusahaan dan tim. Selain itu, saya juga termotivasi oleh lingkungan kerja yang kolaboratif dan kesempatan untuk mengembangkan karir.';
                    } else {
                        // Generic essay answer
                        $essayAnswer = 'Saya percaya bahwa pengalaman dan pengetahuan yang saya miliki dapat memberikan kontribusi yang berarti untuk posisi ini. Saya selalu berusaha untuk memberikan yang terbaik dalam setiap tugas yang diberikan dan terus belajar untuk meningkatkan kemampuan saya.';
                    }
                    
                    // For essay questions, we don't set a score in seeder (will be scored manually)
                    UserAnswer::create([
                        'user_id' => $application->user_id,
                        'question_id' => $question->id,
                        'application_id' => $application->id,
                        'choice_id' => null,
                        'answer_text' => $essayAnswer,
                        'score' => null, // Will be scored manually by admin
                        'answered_at' => now(),
                    ]);
                    continue;
                }

                // Handle multiple choice questions
                $choices = $question->choices;
                
                if ($choices->isEmpty()) {
                    continue;
                }

                // Simulate realistic answer patterns:
                // 70% chance of correct answer for good performance
                $isCorrect = rand(1, 100) <= 70;
                
                if ($isCorrect) {
                    $selectedChoice = $choices->where('is_correct', true)->first();
                    if (!$selectedChoice) {
                        // Fallback to random choice if no correct answer found
                        $selectedChoice = $choices->random();
                    }
                } else {
                    // Select a wrong answer
                    $wrongChoices = $choices->where('is_correct', false);
                    if ($wrongChoices->isNotEmpty()) {
                        $selectedChoice = $wrongChoices->random();
                    } else {
                        // If no wrong choices, select any random choice
                        $selectedChoice = $choices->random();
                    }
                }

                if ($selectedChoice) {
                    UserAnswer::create([
                        'user_id' => $application->user_id,
                        'question_id' => $question->id,
                        'application_id' => $application->id,
                        'choice_id' => $selectedChoice->id,
                        'answer_text' => null,
                        'score' => null,
                        'answered_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('User answers seeding completed successfully.');
    }
} 