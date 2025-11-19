<?php

namespace Database\Seeders;

use App\Models\CandidatesCourse;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan semua courses
        CandidatesCourse::query()->delete();
        
        // Tidak membuat courses untuk candidates
        $this->command->info('Candidates courses cleared.');
    }
} 