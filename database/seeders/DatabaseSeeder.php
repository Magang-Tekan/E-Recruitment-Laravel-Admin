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
            
            // 3. Status Data (Required for Applications)
            StatusSeeder::class,
            
            // 4. Question System (QuestionPack depends on Question)
            QuestionSeeder::class,
            QuestionPackSeeder::class,
            
            // 5. Vacancy System (Depends on: Company, Department, MasterMajor, VacancyType, User, QuestionPack)
            VacanciesSeeder::class,
            
            // 6. Period System (Depends on: Vacancies)
            PeriodSeeder::class,
            
            // 7. Vacancy Periods (Depends on: Vacancies, Period)
            VacancyPeriodsSeeder::class,
            
            // 8. Company Information (Depends on: Company)
            AboutUsSeeder::class,
            
            // 9. Candidate Data (Depends on: User with role CANDIDATE)
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
            
            // 10. Application Flow (Depends on: User, VacancyPeriods, Status)
            ApplicationSeeder::class,
            
            // 11. App Settings
            AppSettingsSeeder::class,
        ]);
    }
}
