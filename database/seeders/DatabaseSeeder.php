<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Basic User Management (No Dependencies)
            UserSeeder::class,
            SuperAdminSeeder::class,
            
            // 2. Master Data (No Dependencies)
            CompanySeeder::class,
            MasterMajorSeeder::class,
            DepartmentSeeder::class,
            VacancyTypeSeeder::class,
            EducationLevelSeeder::class,
            ContactSeeder::class,
            ContactMessageSeeder::class,
            
            
            // 3. Question System (QuestionPack depends on Question)
            QuestionSeeder::class,
            QuestionPackSeeder::class,
            
            // 4. Vacancy System (Depends on: Company, Department, MasterMajor, VacancyType, User, QuestionPack)
            VacanciesSeeder::class,
            
            // 5. Period System (Depends on: Vacancies)
            PeriodSeeder::class,
            
            // 6. Vacancy Periods (Depends on: Vacancies, Period)
            VacancyPeriodsSeeder::class,
            
            // 7. Company Information (Depends on: Company)
            AboutUsSeeder::class,
            
            // 8. Candidate Data (Depends on: User with role CANDIDATE)
            CandidatesProfileSeeder::class,
            CandidatesEducationSeeder::class,
            CandidatesWorkExperienceSeeder::class,
            CandidatesSkillSeeder::class,
            CandidatesLanguageSeeder::class,
            CandidatesCourseSeeder::class,
            CandidatesCertificationSeeder::class,
            CandidatesSocialMediaSeeder::class,
            CandidatesOrganizationSeeder::class,
            CandidatesAchievementSeeder::class,
            CandidatesCVSeeder::class,
            
            // 9. User Test Answers (Depends on: User, Question)
            // UserAnswerSeeder::class,
            
        ]);
    }
}
