<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\QuestionPack;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        
        // Create question packs - ensuring all test types have multiple packs
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
            [
                'pack_name' => 'General Aptitude Test',
                'description' => 'Comprehensive general aptitude assessment',
                'test_type' => 'General',
                'duration' => 45,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(45),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Technical Test Type
            [
                'pack_name' => 'Technical Assessment - IT',
                'description' => 'Technical assessment for IT positions',
                'test_type' => 'Technical',
                'duration' => 45,
                'opens_at' => now()->addDays(2),
                'closes_at' => now()->addDays(45),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Technical Skills Evaluation',
                'description' => 'Advanced technical skills assessment',
                'test_type' => 'Technical',
                'duration' => 60,
                'opens_at' => now()->addDays(2),
                'closes_at' => now()->addDays(60),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Leadership Test Type
            [
                'pack_name' => 'Leadership Assessment',
                'description' => 'Assessment for managerial positions',
                'test_type' => 'Leadership',
                'duration' => 60,
                'opens_at' => now()->addDays(3),
                'closes_at' => now()->addDays(60),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Leadership Potential Test',
                'description' => 'Evaluation of leadership capabilities and potential',
                'test_type' => 'Leadership',
                'duration' => 75,
                'opens_at' => now()->addDays(3),
                'closes_at' => now()->addDays(75),
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
            [
                'pack_name' => 'Logical Reasoning Assessment',
                'description' => 'Advanced logical reasoning and analytical thinking test',
                'test_type' => 'Logic',
                'duration' => 60,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(60),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Emotional Test Type (Psychological)
            [
                'pack_name' => 'Emotional Intelligence Test',
                'description' => 'Assessment for emotional intelligence and empathy',
                'test_type' => 'Emotional',
                'duration' => 60,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(60),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Emotional Quotient Assessment',
                'description' => 'Comprehensive emotional intelligence evaluation',
                'test_type' => 'Emotional',
                'duration' => 75,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(75),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Psychological Test - Emotional',
                'description' => 'Psychological assessment focusing on emotional aspects',
                'test_type' => 'Emotional',
                'duration' => 90,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(90),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            
            // Personality Test Type (Psychological)
            [
                'pack_name' => 'Personality Assessment',
                'description' => 'Comprehensive personality evaluation test',
                'test_type' => 'Personality',
                'duration' => 90,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(90),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Personality Profile Test',
                'description' => 'Detailed personality profiling and analysis',
                'test_type' => 'Personality',
                'duration' => 105,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(105),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Psychological Test - Basic',
                'description' => 'Basic psychological assessment for personality and cognitive evaluation',
                'test_type' => 'Personality',
                'duration' => 90,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(90),
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'pack_name' => 'Psychological Test - Advanced',
                'description' => 'Advanced psychological assessment for senior positions',
                'test_type' => 'Personality',
                'duration' => 120,
                'opens_at' => now()->addDays(1),
                'closes_at' => now()->addDays(120),
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