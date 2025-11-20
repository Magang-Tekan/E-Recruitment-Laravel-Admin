<?php

namespace Database\Seeders;

use App\Models\VacancyPeriods;
use App\Models\Vacancies;
use App\Models\Period;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VacancyPeriodsSeeder extends Seeder
{
    public function run(): void
    {
        // Temporarily disable foreign key constraints
        Schema::disableForeignKeyConstraints();
        
        try {
            // Delete existing records instead of truncating
            DB::table('vacancy_periods')->delete();

            // Get all vacancies and periods
            $vacancies = Vacancies::all();
            $periods = Period::all();

            if ($vacancies->isEmpty() || $periods->isEmpty()) {
                $this->command->info('No vacancies or periods found. Skipping vacancy_periods seeding.');
                return;
            }

            $vacancyPeriods = [];

            // Create periods for all vacancies
            foreach ($vacancies as $index => $vacancy) {
                // Special case for INTERNSHIP, Software Engineer, and Data Analyst - assign to current OPEN period (Q4 - period id 4)
                // Use title instead of id for reliability
                if ($vacancy->title === 'INTERNSHIP' || $vacancy->title === 'Software Engineer' || $vacancy->title === 'Data Analyst') {
                    $vacancyPeriods[] = [
                        'vacancy_id' => $vacancy->id,
                        'period_id' => 4, // Q4 2025 Recruitment (current OPEN period)
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                } else {
                    // Determine which period to assign based on vacancy index for other vacancies
                    $periodId = ($index % 4) + 1; // This will cycle through periods 1-4

                    $vacancyPeriods[] = [
                        'vacancy_id' => $vacancy->id,
                        'period_id' => $periodId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Optionally assign a second period to some vacancies
                    if ($index % 2 == 0) { // Every other vacancy gets a second period
                        $secondPeriodId = (($periodId + 1) > 4) ? 1 : ($periodId + 1);
                        $vacancyPeriods[] = [
                            'vacancy_id' => $vacancy->id,
                            'period_id' => $secondPeriodId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            // Batch insert all vacancy periods
            if (!empty($vacancyPeriods)) {
                DB::table('vacancy_periods')->insert($vacancyPeriods);
            }

            $this->command->info('Vacancy periods seeded successfully! Created ' . count($vacancyPeriods) . ' vacancy-period relationships.');

        } catch (\Exception $e) {
            $this->command->error('Error seeding vacancy periods: ' . $e->getMessage());
        } finally {
            // Re-enable foreign key constraints
            Schema::enableForeignKeyConstraints();
        }
    }
}
