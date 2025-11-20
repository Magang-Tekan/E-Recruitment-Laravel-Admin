<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\QuestionPack;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class QuestionPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get an HR user to associate with the question packs
        $user = User::where('role', UserRole::HR->value)->first();
        
        if (!$user) {
            $this->command->info('No HR user found. Skipping question pack seeding.');
            return;
        }
        
        // Hapus semua question packs yang ada
        QuestionPack::query()->delete();
        
        // Get all questions
        $allQuestions = Question::all();
        $multipleChoiceQuestions = $allQuestions->where('question_type', 'multiple_choice');
        $essayQuestions = $allQuestions->where('question_type', 'essay');
        
        // Create INTERNSHIP question pack with 3 essay and 2 multiple choice
        $internshipPack = QuestionPack::create([
            'pack_name' => 'INTERNSHIP',
            'description' => 'Assessment test for internship program',
            'test_type' => 'General',
            'duration' => 60,
            'opens_at' => Carbon::create(2025, 1, 11, 0, 0, 0), // 11-01-2025
            'closes_at' => Carbon::create(2026, 1, 1, 23, 59, 59), // 01-01-2026
            'user_id' => $user->id,
            'status' => 'active'
        ]);
        
        // Attach 2 multiple choice questions
        $mcQuestions = $multipleChoiceQuestions->take(2);
        foreach ($mcQuestions as $question) {
            $internshipPack->questions()->attach($question->id);
        }
        
        // Attach 3 essay questions
        $essaySelected = $essayQuestions->take(3);
        foreach ($essaySelected as $question) {
            $internshipPack->questions()->attach($question->id);
        }
        
        // Create other question packs - hanya beberapa pack penting saja
        $questionPacks = [
            // General Test Type
            [
                'pack_name' => 'General Assessment',
                'description' => 'Basic assessment for all candidates',
                'test_type' => 'General',
                'duration' => 30,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(30),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Technical Test Type
            [
                'pack_name' => 'Technical Assessment - IT',
                'description' => 'Technical assessment for IT positions',
                'test_type' => 'Technical',
                'duration' => 45,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(45),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Logic Test Type
            [
                'pack_name' => 'Logic Test',
                'description' => 'Logical reasoning and problem-solving assessment',
                'test_type' => 'Logic',
                'duration' => 45,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(45),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Psychology Test Type
            [
                'pack_name' => 'Psychology Assessment',
                'description' => 'Comprehensive psychological evaluation test',
                'test_type' => 'Psychology',
                'duration' => 90,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(90),
                'user_id' => $user->id,
                'status' => 'active'
            ],
        ];

        foreach ($questionPacks as $packData) {
            $pack = QuestionPack::create($packData);
            
            // Attach some random questions to each pack
            $questions = Question::inRandomOrder()->take(rand(5, 8))->get();
            foreach ($questions as $question) {
                $pack->questions()->attach($question->id);
            }
        }

        $this->command->info('Question packs seeded successfully with psychological test types.');
    }
}