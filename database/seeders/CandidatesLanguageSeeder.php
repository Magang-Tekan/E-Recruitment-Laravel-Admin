<?php

namespace Database\Seeders;

use App\Models\CandidatesLanguage;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan semua languages
        CandidatesLanguage::query()->delete();
        
        // Tidak membuat languages untuk candidates
        $this->command->info('Candidates languages cleared.');
    }
} 