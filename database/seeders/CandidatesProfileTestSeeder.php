<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\CandidatesProfile;
use App\Models\CandidatesEducation;
use App\Models\CandidatesWorkExperience;
use App\Models\CandidatesSkill;
use App\Models\CandidatesSocialMedia;
use App\Models\CandidatesLanguage;
use App\Models\CandidatesCertification;
use App\Models\CandidatesAchievement;
use App\Models\EducationLevel;
use App\Models\MasterMajor;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesProfileTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $candidates = User::where('role', UserRole::CANDIDATE->value)->get();
        
        if ($candidates->isEmpty()) {
            $this->command->error('No candidates found. Please run UserSeeder first.');
            return;
        }

        // Budi Santoso - Kandidat untuk Psikolog Klinis
        $budi = $candidates->where('name', 'Budi Santoso')->first();
        if ($budi) {
            $this->createBudiProfile($budi);
        }

        // Sari Dewi - Kandidat untuk Software Developer
        $sari = $candidates->where('name', 'Sari Dewi')->first();
        if ($sari) {
            $this->createSariProfile($sari);
        }

        $this->command->info('Created complete profiles for 2 test candidates');
    }

    private function createBudiProfile(User $user)
    {
        // Basic Profile
        CandidatesProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'no_ektp' => '3174012801950001',
                'gender' => 'male',
                'phone_number' => '081234567890',
                'npwp' => '12.345.678.9-012.000',
                'about_me' => 'Saya adalah psikolog dengan passion dalam membantu orang mengatasi masalah mental dan emosional. Memiliki pengalaman dalam konseling individu dan grup therapy.',
                'place_of_birth' => 'Jakarta',
                'date_of_birth' => '1995-01-28',
                'address' => 'Jl. Sudirman No. 123, RT 001 RW 002',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Pusat',
                'district' => 'Tanah Abang',
                'village' => 'Gelora',
                'rt' => '001',
                'rw' => '002',
            ]
        );

        // Education
        $s1Level = EducationLevel::where('name', 'like', '%S1%')->first();
        $psychologyMajor = MasterMajor::where('name', 'like', '%Psikologi%')->first() ?? MasterMajor::first();

        if ($s1Level && $psychologyMajor) {
            CandidatesEducation::updateOrCreate(
                ['user_id' => $user->id, 'institution_name' => 'Universitas Indonesia'],
                [
                    'education_level_id' => $s1Level->id,
                    'major_id' => $psychologyMajor->id,
                    'faculty' => 'Fakultas Psikologi',
                    'institution_name' => 'Universitas Indonesia',
                    'year_in' => 2013,
                    'year_out' => 2017,
                    'gpa' => 3.75,
                ]
            );
        }

        // Work Experience - using correct column names
        CandidatesWorkExperience::updateOrCreate(
            ['user_id' => $user->id, 'job_title' => 'Psikolog Klinis'],
            [
                'job_title' => 'Psikolog Klinis',
                'employment_status' => 'full_time',
                'job_description' => 'Melakukan assessment psikologi, terapi individu dan grup, serta konseling untuk pasien dengan berbagai gangguan mental',
                'is_current_job' => false,
                'start_month' => 8,
                'start_year' => 2020,
                'end_month' => 12,
                'end_year' => 2023,
            ]
        );

        CandidatesWorkExperience::updateOrCreate(
            ['user_id' => $user->id, 'job_title' => 'Psikolog Konselor'],
            [
                'job_title' => 'Psikolog Konselor',
                'employment_status' => 'full_time',
                'job_description' => 'Menangani kasus konseling pernikahan, terapi anak, dan assessment psikologi untuk berbagai keperluan',
                'is_current_job' => true,
                'start_month' => 1,
                'start_year' => 2024,
                'end_month' => null,
                'end_year' => null,
            ]
        );

        // Skills
        $psychologySkills = [
            'Psychological Assessment',
            'Cognitive Behavioral Therapy', 
            'Individual Counseling',
            'Group Therapy'
        ];

        foreach ($psychologySkills as $skill) {
            CandidatesSkill::updateOrCreate(
                ['user_id' => $user->id, 'skill_name' => $skill],
                ['certificate_file' => null]
            );
        }

        $this->command->info("✅ Created complete profile for Budi Santoso (Psikolog)");
    }

    private function createSariProfile(User $user)
    {
        // Basic Profile
        CandidatesProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'no_ektp' => '3271035602900002',
                'gender' => 'female',
                'phone_number' => '081987654321',
                'npwp' => '98.765.432.1-123.000',
                'about_me' => 'Passionate software developer dengan pengalaman 5+ tahun dalam web development. Menguasai full-stack development dan memiliki pengalaman memimpin tim development.',
                'place_of_birth' => 'Bandung',
                'date_of_birth' => '1990-02-15',
                'address' => 'Jl. Dago No. 456, RT 003 RW 004',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'district' => 'Coblong',
                'village' => 'Dago',
                'rt' => '003',
                'rw' => '004',
            ]
        );

        // Education
        $s1Level = EducationLevel::where('name', 'like', '%S1%')->first();
        $informatikaMajor = MasterMajor::where('name', 'like', '%Informatika%')->first() ?? MasterMajor::first();

        if ($s1Level && $informatikaMajor) {
            CandidatesEducation::updateOrCreate(
                ['user_id' => $user->id, 'institution_name' => 'Institut Teknologi Bandung'],
                [
                    'education_level_id' => $s1Level->id,
                    'major_id' => $informatikaMajor->id,
                    'faculty' => 'Sekolah Teknik Elektro dan Informatika',
                    'institution_name' => 'Institut Teknologi Bandung',
                    'year_in' => 2008,
                    'year_out' => 2012,
                    'gpa' => 3.68,
                ]
            );
        }

        // Work Experience using correct column structure
        CandidatesWorkExperience::updateOrCreate(
            ['user_id' => $user->id, 'job_title' => 'Junior Software Engineer'],
            [
                'job_title' => 'Junior Software Engineer',
                'employment_status' => 'full_time',
                'job_description' => 'Mengembangkan fitur e-commerce platform menggunakan PHP, MySQL, dan JavaScript',
                'is_current_job' => false,
                'start_month' => 8,
                'start_year' => 2012,
                'end_month' => 12,
                'end_year' => 2015,
            ]
        );

        CandidatesWorkExperience::updateOrCreate(
            ['user_id' => $user->id, 'job_title' => 'Tech Lead'],
            [
                'job_title' => 'Tech Lead',
                'employment_status' => 'full_time',
                'job_description' => 'Memimpin tim development untuk fitur payment system, architecture design, dan code review',
                'is_current_job' => true,
                'start_month' => 7,
                'start_year' => 2020,
                'end_month' => null,
                'end_year' => null,
            ]
        );

        // Skills
        $techSkills = [
            'PHP', 'Laravel', 'JavaScript', 'React.js', 'MySQL', 'Git'
        ];

        foreach ($techSkills as $skill) {
            CandidatesSkill::updateOrCreate(
                ['user_id' => $user->id, 'skill_name' => $skill],
                ['certificate_file' => null]
            );
        }

        $this->command->info("✅ Created complete profile for Sari Dewi (Software Developer)");
    }
}
