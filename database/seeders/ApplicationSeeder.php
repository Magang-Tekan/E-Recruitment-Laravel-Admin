<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationHistory;
use App\Models\User;
use App\Models\VacancyPeriods;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing applications and history (handle foreign key constraints properly)
        ApplicationHistory::query()->delete();
        Application::query()->delete();

        // Get only specific candidates: 'Candidate User', 'Candidate User 2', and 'userbiasa'
        $specificCandidates = User::where('role', UserRole::CANDIDATE)
            ->whereIn('name', ['Candidate User', 'Candidate User 2', 'userbiasa'])
            ->get();

        $statuses = Status::all();

        if ($specificCandidates->count() < 3) {
            $this->command->info('Required candidates not found. Make sure these users exist:');
            $this->command->info('- Candidate User');
            $this->command->info('- Candidate User 2'); 
            $this->command->info('- userbiasa');
            return;
        }

        if ($statuses->isEmpty()) {
            $this->command->info('No statuses found. Run migration or StatusSeeder first.');
            return;
        }

        // Get Software Engineer and Data Analyst OPEN periods for psychological test
        $currentDate = Carbon::now();
        
        // Get Software Engineer periods
        $softwareEngineerPeriods = VacancyPeriods::with(['vacancy.company', 'vacancy.department', 'vacancy.questionPack', 'period'])
            ->whereHas('vacancy', function($query) {
                $query->where('title', 'Software Engineer');
            })
            ->whereHas('period', function($query) use ($currentDate) {
                $query->where('start_time', '<=', $currentDate)
                      ->where('end_time', '>=', $currentDate);
            })
            ->get();
            
        // Get Data Analyst periods
        $dataAnalystPeriods = VacancyPeriods::with(['vacancy.company', 'vacancy.department', 'vacancy.questionPack', 'period'])
            ->whereHas('vacancy', function($query) {
                $query->where('title', 'Data Analyst');
            })
            ->whereHas('period', function($query) use ($currentDate) {
                $query->where('start_time', '<=', $currentDate)
                      ->where('end_time', '>=', $currentDate);
            })
            ->get();

        if ($softwareEngineerPeriods->isEmpty() && $dataAnalystPeriods->isEmpty()) {
            $this->command->info('No open Software Engineer or Data Analyst vacancy periods found.');
            return;
        }

        $adminSelectionStatus = $statuses->where('code', 'admin_selection')->first();
        $psychotestStatus = $statuses->where('code', 'psychotest')->first();
        $interviewStatus = $statuses->where('code', 'interview')->first();
        $acceptedStatus = $statuses->where('code', 'accepted')->first();
        $rejectedStatus = $statuses->where('code', 'rejected')->first();

        $this->command->info('Creating applications for 3 specific candidates...');

        // Create applications for the 3 specific candidates with specific job assignments
        foreach ($specificCandidates as $index => $candidate) {
            $selectedVacancyPeriod = null;
            
            // Assign specific vacancies based on candidate name
            if ($candidate->name === 'Candidate User 2') {
                // Candidate User 2 applies to Data Analyst
                if (!$dataAnalystPeriods->isEmpty()) {
                    $selectedVacancyPeriod = $dataAnalystPeriods->first();
                    $this->command->info("Assigning {$candidate->name} to Data Analyst position");
                }
            } else {
                // Other candidates apply to Software Engineer
                if (!$softwareEngineerPeriods->isEmpty()) {
                    $selectedVacancyPeriod = $softwareEngineerPeriods->random(1)->first();
                    $this->command->info("Assigning {$candidate->name} to Software Engineer position");
                }
            }
            
            if (!$selectedVacancyPeriod) {
                $this->command->info("No suitable vacancy period found for {$candidate->name}");
                continue;
            }

            // Check if this combination already exists to avoid duplicates
            $existingApplication = Application::where('user_id', $candidate->id)
                ->where('vacancy_period_id', $selectedVacancyPeriod->id)
                ->first();

            if ($existingApplication) {
                $this->command->info("Skipping duplicate: {$candidate->name} -> {$selectedVacancyPeriod->vacancy->title}");
                continue;
            }

            // Create the application
            $application = Application::create([
                'user_id' => $candidate->id,
                'vacancy_period_id' => $selectedVacancyPeriod->id,
                'status_id' => $adminSelectionStatus->id,
                'resume_path' => null,
                'cover_letter_path' => null,
                'created_at' => Carbon::now()->subDays(rand(5, 15)),
            ]);

            $this->command->info("Created application for {$candidate->name} -> {$selectedVacancyPeriod->vacancy->title}");

            // Create realistic recruitment flow for testing
            $this->createRealisticRecruitmentFlow($application, [
                'admin_selection' => $adminSelectionStatus,
                'psychotest' => $psychotestStatus,
                'interview' => $interviewStatus,
                'accepted' => $acceptedStatus,
                'rejected' => $rejectedStatus,
            ], $index, $softwareEngineerPeriods->count());
        }

        $this->command->info('Applications seeded successfully for 3 specific candidates!');
        $this->command->info('Candidates applied to Software Engineer (Psychological Test):');
        $this->command->info('- Candidate User');
        $this->command->info('- Candidate User 2');
        $this->command->info('- userbiasa');
        $this->command->info('Flow: ALL applications in admin_selection stage with consistent dates');
        $this->command->info('Ready for psychological test workflow testing');
    }

    /**
     * Create simple administrative flow - all candidates stay in admin stage only
     * Focus on consistent dates and admin_selection stage testing
     */
    private function createRealisticRecruitmentFlow(Application $application, array $statuses, int $appIndex, int $totalOpenPeriods): void
    {
        // Use a consistent base date for all applications
        $baseDate = Carbon::now()->subDays(5); // 5 days ago for consistency
        $application->update([
            'created_at' => $baseDate,
            'updated_at' => $baseDate
        ]);

        // Stage 1: Administrative Selection (ALL applications stay here without review)
        ApplicationHistory::create([
            'application_id' => $application->id,
            'status_id' => $statuses['admin_selection']->id,
            'processed_at' => $baseDate->copy(),
            'notes' => 'Application received and under administrative review',
            'reviewed_by' => null,
            'score' => null, // No score initially
            'reviewed_at' => null,
            'completed_at' => null, // Not completed yet
        ]);

        // Update application status to admin_selection
        $application->update(['status_id' => $statuses['admin_selection']->id]);

        $this->command->info("  -> Application in admin_selection stage - Ready for manual review (Started: {$baseDate->format('Y-m-d H:i:s')})");
    }
}
