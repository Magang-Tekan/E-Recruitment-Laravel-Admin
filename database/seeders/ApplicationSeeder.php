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

        // Get candidates
        $budiSantoso = User::where('role', UserRole::CANDIDATE)
            ->where(function($query) {
                $query->where('name', 'Budi Santoso')
                      ->orWhere('email', 'budi.santoso@gmail.com');
            })
            ->first();

        if ($budiSantoso) {
            $this->command->info('Found Budi Santoso user: ID=' . $budiSantoso->id . ', Email=' . $budiSantoso->email);
        } else {
            $this->command->warn('Budi Santoso user NOT FOUND! Make sure UserSeeder has been run.');
        }

        // Get user with Teknik Informatika education for Software Engineer
        $softwareEngineerCandidate = User::where('role', UserRole::CANDIDATE)
            ->where(function($query) {
                $query->where('email', 'ahmad.fauzi@gmail.com')
                      ->orWhere('name', 'Ahmad Fauzi');
            })
            ->first();

        if ($softwareEngineerCandidate) {
            $this->command->info('Found Ahmad Fauzi user: ID=' . $softwareEngineerCandidate->id . ', Email=' . $softwareEngineerCandidate->email);
        } else {
            $this->command->warn('Ahmad Fauzi user NOT FOUND! Make sure UserSeeder has been run.');
        }

        // Get John Doe for Software Engineer application
        $johnDoe = User::where('role', UserRole::CANDIDATE)
            ->where(function($query) {
                $query->where('email', 'john.doe@example.com')
                      ->orWhere('name', 'John Doe');
            })
            ->first();

        if ($johnDoe) {
            $this->command->info('Found John Doe user: ID=' . $johnDoe->id . ', Email=' . $johnDoe->email);
        } else {
            $this->command->warn('John Doe user NOT FOUND! Make sure UserSeeder has been run.');
        }

        $statuses = Status::all();

        if ($statuses->isEmpty()) {
            $this->command->info('No statuses found. Run migration or StatusSeeder first.');
            return;
        }

        $adminSelectionStatus = $statuses->where('code', 'admin_selection')->first();
        $psychotestStatus = $statuses->where('code', 'psychotest')->first();
        $interviewStatus = $statuses->where('code', 'interview')->first();
        $acceptedStatus = $statuses->where('code', 'accepted')->first();
        $rejectedStatus = $statuses->where('code', 'rejected')->first();

        // Get INTERNSHIP OPEN periods only (must be currently active)
        $currentDate = Carbon::now();
        
        $internshipPeriods = VacancyPeriods::with(['vacancy.company', 'vacancy.department', 'vacancy.questionPack', 'period'])
            ->whereHas('vacancy', function($query) {
                $query->where('title', 'INTERNSHIP');
            })
            ->whereHas('period', function($query) use ($currentDate) {
                $query->where('start_time', '<=', $currentDate)
                      ->where('end_time', '>=', $currentDate);
            })
            ->get();

        if ($internshipPeriods->isEmpty()) {
            $this->command->warn('WARNING: No OPEN INTERNSHIP periods found! Make sure there is an active period (start_time <= now <= end_time).');
            $this->command->warn('Current date: ' . $currentDate->format('Y-m-d H:i:s'));
            $this->command->warn('Skipping INTERNSHIP applications - they will not be created.');
            
            // Debug: Check if INTERNSHIP vacancy exists
            $internshipVacancy = \App\Models\Vacancies::where('title', 'INTERNSHIP')->first();
            if ($internshipVacancy) {
                $this->command->info('INTERNSHIP vacancy exists (ID: ' . $internshipVacancy->id . ')');
                // Check all periods for this vacancy
                $allInternshipPeriods = VacancyPeriods::with('period')
                    ->where('vacancy_id', $internshipVacancy->id)
                    ->get();
                if ($allInternshipPeriods->isEmpty()) {
                    $this->command->warn('INTERNSHIP has NO periods assigned! Run VacancyPeriodsSeeder.');
                } else {
                    $this->command->info('INTERNSHIP has ' . $allInternshipPeriods->count() . ' period(s) assigned:');
                    foreach ($allInternshipPeriods as $vp) {
                        $period = $vp->period;
                        $isOpen = $period && 
                                 $period->start_time <= $currentDate && 
                                 $period->end_time >= $currentDate;
                        $this->command->info('  - Period ID ' . $period->id . ': ' . $period->name . 
                                           ' (' . $period->start_time . ' to ' . $period->end_time . ') - ' . 
                                           ($isOpen ? 'OPEN' : 'CLOSED'));
                    }
                }
            } else {
                $this->command->warn('INTERNSHIP vacancy NOT FOUND! Run VacanciesSeeder.');
            }
        } else {
            $this->command->info('Found ' . $internshipPeriods->count() . ' OPEN INTERNSHIP period(s).');
            foreach ($internshipPeriods as $vp) {
                $this->command->info('  - Period: ' . $vp->period->name . ' (ID: ' . $vp->period->id . ')');
            }
        }

        // Get Software Engineer OPEN periods only (must be currently active)
        $softwareEngineerPeriods = VacancyPeriods::with(['vacancy.company', 'vacancy.department', 'vacancy.questionPack', 'period'])
            ->whereHas('vacancy', function($query) {
                $query->where('title', 'Software Engineer');
            })
            ->whereHas('period', function($query) use ($currentDate) {
                $query->where('start_time', '<=', $currentDate)
                      ->where('end_time', '>=', $currentDate);
            })
            ->get();

        if ($softwareEngineerPeriods->isEmpty()) {
            $this->command->warn('WARNING: No OPEN Software Engineer periods found! Make sure there is an active period (start_time <= now <= end_time).');
            $this->command->warn('Current date: ' . $currentDate->format('Y-m-d H:i:s'));
            $this->command->warn('Skipping Software Engineer applications - they will not be created.');
            
            // Debug: Check if Software Engineer vacancy exists
            $seVacancy = \App\Models\Vacancies::where('title', 'Software Engineer')->first();
            if ($seVacancy) {
                $this->command->info('Software Engineer vacancy exists (ID: ' . $seVacancy->id . ')');
                // Check all periods for this vacancy
                $allSEPeriods = VacancyPeriods::with('period')
                    ->where('vacancy_id', $seVacancy->id)
                    ->get();
                if ($allSEPeriods->isEmpty()) {
                    $this->command->warn('Software Engineer has NO periods assigned! Run VacancyPeriodsSeeder.');
                } else {
                    $this->command->info('Software Engineer has ' . $allSEPeriods->count() . ' period(s) assigned:');
                    foreach ($allSEPeriods as $vp) {
                        $period = $vp->period;
                        $isOpen = $period && 
                                 $period->start_time <= $currentDate && 
                                 $period->end_time >= $currentDate;
                        $this->command->info('  - Period ID ' . $period->id . ': ' . $period->name . 
                                           ' (' . $period->start_time . ' to ' . $period->end_time . ') - ' . 
                                           ($isOpen ? 'OPEN' : 'CLOSED'));
                    }
                }
            } else {
                $this->command->warn('Software Engineer vacancy NOT FOUND! Run VacanciesSeeder.');
            }
        } else {
            $this->command->info('Found ' . $softwareEngineerPeriods->count() . ' OPEN Software Engineer period(s).');
            foreach ($softwareEngineerPeriods as $vp) {
                $this->command->info('  - Period: ' . $vp->period->name . ' (ID: ' . $vp->period->id . ')');
            }
        }

        // Create application for Budi Santoso to INTERNSHIP
        // Only create if there is an OPEN period
        if ($budiSantoso && !$internshipPeriods->isEmpty()) {
            $this->command->info('Creating application for Budi Santoso to INTERNSHIP...');
            $selectedVacancyPeriod = $internshipPeriods->first();

            $existingApplication = Application::where('user_id', $budiSantoso->id)
                ->where('vacancy_period_id', $selectedVacancyPeriod->id)
                ->first();

            if (!$existingApplication) {
                $application = Application::create([
                    'user_id' => $budiSantoso->id,
                    'vacancy_period_id' => $selectedVacancyPeriod->id,
                    'status_id' => $adminSelectionStatus->id,
                    'resume_path' => null,
                    'cover_letter_path' => null,
                    'created_at' => Carbon::now()->subDays(5),
                ]);

                $this->command->info("Created application for Budi Santoso -> INTERNSHIP");

                $this->createRealisticRecruitmentFlow($application, [
                    'admin_selection' => $adminSelectionStatus,
                    'psychotest' => $psychotestStatus,
                    'interview' => $interviewStatus,
                    'accepted' => $acceptedStatus,
                    'rejected' => $rejectedStatus,
                ], 0, 1);
            } else {
                $this->command->info("Skipping duplicate: Budi Santoso -> INTERNSHIP");
            }
        } else {
            if (!$budiSantoso) {
                $this->command->warn('Budi Santoso user not found. Cannot create application.');
            } elseif ($internshipPeriods->isEmpty()) {
                $this->command->warn('No OPEN INTERNSHIP periods found. Cannot create application for Budi Santoso.');
            }
        }

        // Create application for Ahmad Fauzi (Teknik Informatika) to Software Engineer
        // Only create if there is an OPEN period
        if ($softwareEngineerCandidate && !$softwareEngineerPeriods->isEmpty()) {
            $this->command->info('Creating application for Ahmad Fauzi to Software Engineer...');
            $selectedVacancyPeriod = $softwareEngineerPeriods->first();

            $existingApplication = Application::where('user_id', $softwareEngineerCandidate->id)
                ->where('vacancy_period_id', $selectedVacancyPeriod->id)
                ->first();

            if (!$existingApplication) {
                $application = Application::create([
                    'user_id' => $softwareEngineerCandidate->id,
                    'vacancy_period_id' => $selectedVacancyPeriod->id,
                    'status_id' => $adminSelectionStatus->id,
                    'resume_path' => null,
                    'cover_letter_path' => null,
                    'created_at' => Carbon::now()->subDays(3),
                ]);

                $this->command->info("Created application for Ahmad Fauzi -> Software Engineer");

                $this->createRealisticRecruitmentFlow($application, [
                    'admin_selection' => $adminSelectionStatus,
                    'psychotest' => $psychotestStatus,
                    'interview' => $interviewStatus,
                    'accepted' => $acceptedStatus,
                    'rejected' => $rejectedStatus,
                ], 0, 1);
            } else {
                $this->command->info("Skipping duplicate: Ahmad Fauzi -> Software Engineer");
            }
        } else {
            if (!$softwareEngineerCandidate) {
                $this->command->warn('Ahmad Fauzi user not found. Cannot create application.');
            } elseif ($softwareEngineerPeriods->isEmpty()) {
                $this->command->warn('No OPEN Software Engineer periods found. Cannot create application for Ahmad Fauzi.');
            }
        }

        // Create application for John Doe (Teknik Informatika) to Software Engineer
        // Only create if there is an OPEN period
        if ($johnDoe && !$softwareEngineerPeriods->isEmpty()) {
            $this->command->info('Creating application for John Doe to Software Engineer...');
            $selectedVacancyPeriod = $softwareEngineerPeriods->first();

            $existingApplication = Application::where('user_id', $johnDoe->id)
                ->where('vacancy_period_id', $selectedVacancyPeriod->id)
                ->first();

            if (!$existingApplication) {
                $application = Application::create([
                    'user_id' => $johnDoe->id,
                    'vacancy_period_id' => $selectedVacancyPeriod->id,
                    'status_id' => $adminSelectionStatus->id,
                    'resume_path' => null,
                    'cover_letter_path' => null,
                    'created_at' => Carbon::now()->subDays(2),
                ]);

                $this->command->info("Created application for John Doe -> Software Engineer");

                $this->createRealisticRecruitmentFlow($application, [
                    'admin_selection' => $adminSelectionStatus,
                    'psychotest' => $psychotestStatus,
                    'interview' => $interviewStatus,
                    'accepted' => $acceptedStatus,
                    'rejected' => $rejectedStatus,
                ], 0, 1);
            } else {
                $this->command->info("Skipping duplicate: John Doe -> Software Engineer");
            }
        } else {
            if (!$johnDoe) {
                $this->command->warn('John Doe user not found. Cannot create application.');
            } elseif ($softwareEngineerPeriods->isEmpty()) {
                $this->command->warn('No OPEN Software Engineer periods found. Cannot create application for John Doe.');
            }
        }

        $this->command->info('Applications seeded successfully!');
        if ($budiSantoso) {
            $this->command->info('- Budi Santoso applied to INTERNSHIP position');
        }
        if ($softwareEngineerCandidate) {
            $this->command->info('- Ahmad Fauzi applied to Software Engineer position');
        }
        if ($johnDoe) {
            $this->command->info('- John Doe applied to Software Engineer position');
        }
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
