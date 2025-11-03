<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Models\Vacancies;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some vacancies to associate with the periods
        $vacancies = Vacancies::all();
        
        if ($vacancies->isEmpty()) {
            $this->command->info('No vacancies found. Skipping period seeding.');
            return;
        }

        // Create or update periods
        $periods = [];
        
        $periodsData = [
            [
                'id' => 1,
                'name' => 'Q1 2025 Recruitment',
                'description' => 'First quarter recruitment campaign',
                'start_time' => '2025-01-01 00:00:00',
                'end_time' => '2025-03-31 23:59:59',
            ],
            [
                'id' => 2,
                'name' => 'Q2 2025 Recruitment',
                'description' => 'Second quarter recruitment campaign',
                'start_time' => '2025-04-01 00:00:00',
                'end_time' => '2025-06-30 23:59:59',
            ],
            [
                'id' => 3,
                'name' => 'Q3 2025 Recruitment',
                'description' => 'Third quarter recruitment campaign',
                'start_time' => '2025-07-01 00:00:00',
                'end_time' => '2025-09-30 23:59:59',
            ],
            [
                'id' => 4,
                'name' => 'Q4 2025 Recruitment - ACTIVE',
                'description' => 'Fourth quarter recruitment campaign - Currently Open for Applications',
                'start_time' => '2025-10-01 00:00:00',
                'end_time' => '2025-12-31 23:59:59',
            ],
            [
                'id' => 5,
                'name' => 'Year-End 2025 Special Recruitment',
                'description' => 'Special recruitment campaign for urgent positions - Open Now',
                'start_time' => '2025-10-15 00:00:00',
                'end_time' => '2026-01-15 23:59:59',
            ],
        ];

        foreach ($periodsData as $periodData) {
            $period = Period::updateOrCreate(
                ['id' => $periodData['id']],
                $periodData
            );
            $periods[] = $period;
        }

        // Associate periods with vacancies - Focus on OPEN periods (4 & 5)
        foreach ($vacancies as $index => $vacancy) {
            // Clear existing period associations
            $vacancy->periods()->detach();
            
            if ($index <= 2) {
                // First 3 vacancies - Associate with BOTH open periods for maximum testing opportunities
                $vacancy->periods()->attach([$periods[3]->id, $periods[4]->id]); // Q4 and Year-End (both open)
            } elseif ($index <= 5) {
                // Next 3 vacancies - Q4 period only
                $vacancy->periods()->attach([$periods[3]->id]); // Q4 (open)
            } elseif ($index <= 7) {
                // Next 2 vacancies - Year-End period only  
                $vacancy->periods()->attach([$periods[4]->id]); // Year-End (open)
            } else {
                // Remaining vacancies - Mix of open and closed periods
                $availablePeriods = collect($periods)->pluck('id')->toArray();
                shuffle($availablePeriods);
                $selectedPeriods = array_slice($availablePeriods, 0, rand(1, 2));
                $vacancy->periods()->attach($selectedPeriods);
            }
        }

        $this->command->info('Periods seeded successfully!');
    }
}