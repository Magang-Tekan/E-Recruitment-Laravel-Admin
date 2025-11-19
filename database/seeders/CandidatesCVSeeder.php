<?php

namespace Database\Seeders;

use App\Models\CandidatesCV;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesCVSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan semua CV
        CandidatesCV::query()->delete();
        
        // Tidak membuat CV untuk candidates
        $this->command->info('Candidates CV cleared.');
    }

    private function generateCVSnapshot($userName)
    {
        $cvData = [
            'personal_info' => [
                'name' => $userName,
                'profession' => $this->getRandomProfession(),
                'summary' => 'Profesional muda yang berpengalaman dengan passion dalam bidang teknologi dan inovasi. Memiliki kemampuan analitis yang kuat dan orientasi pada hasil. Selalu berusaha memberikan kontribusi terbaik untuk kemajuan perusahaan dan tim.'
            ],
            'skills' => $this->getRandomSkills(),
            'experience_count' => rand(1, 4),
            'education_count' => rand(1, 3),
            'certification_count' => rand(0, 5),
            'language_skills' => ['Bahasa Indonesia (Native)', 'English (Professional)'],
            'last_updated' => now()->format('Y-m-d H:i:s')
        ];

        return json_encode($cvData);
    }

    private function getRandomProfession()
    {
        $professions = [
            'Software Engineer', 'Data Analyst', 'Product Manager', 'UI/UX Designer',
            'Digital Marketing Specialist', 'Business Analyst', 'Project Manager',
            'Financial Analyst', 'Human Resources Specialist', 'Operations Manager',
            'Sales Executive', 'Content Creator', 'Graphic Designer', 'Web Developer',
            'Mobile App Developer', 'System Analyst', 'Quality Assurance Engineer',
            'Customer Success Manager', 'Marketing Manager', 'Account Executive'
        ];

        return $professions[array_rand($professions)];
    }

    private function getRandomSkills()
    {
        $allSkills = [
            'JavaScript', 'Python', 'Java', 'PHP', 'React', 'Vue.js', 'Node.js',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Git', 'Docker', 'AWS',
            'Project Management', 'Team Leadership', 'Communication Skills',
            'Digital Marketing', 'SEO', 'Google Analytics', 'Social Media Marketing',
            'Data Analysis', 'Microsoft Excel', 'PowerBI', 'Tableau',
            'Adobe Photoshop', 'Adobe Illustrator', 'Figma', 'Sketch',
            'Accounting', 'Financial Analysis', 'Budgeting', 'Risk Management'
        ];

        $numberOfSkills = rand(5, 10);
        return array_slice($allSkills, 0, $numberOfSkills);
    }
} 