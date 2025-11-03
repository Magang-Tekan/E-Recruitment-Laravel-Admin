<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationHistory;
use App\Models\Status;
use App\Models\UserAnswer;
use App\Models\Period;
use App\Models\User;
use App\Models\VacancyPeriods;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationStageController extends Controller
{
    private array $stageOrder = [
        'administrative_selection' => 1,
        'psychological_test' => 2,
        'interview' => 3
    ];

    /**
     * Display applications for a specific stage
     */
    public function index(Request $request, string $stage)
    {
        // Get the status for this stage
        $status = Status::where('stage', $stage)->first();
        if (!$status) {
            abort(404, 'Stage not found');
        }

        // Build the base query
        $query = Application::with([
            'user.candidatesProfile', // Basic candidate info
            'vacancyPeriod.vacancy.company', // Company info
            'vacancyPeriod.period', // Period info
            'history' => function ($query) {
                $query->with(['status', 'reviewer'])
                    ->latest(); // Remove is_active filter to show all history
            },
            // Add specific stage history for display
            'currentStageHistory' => function ($query) use ($stage) {
                $query->with(['status', 'reviewer'])
                    ->whereHas('status', fn($q) => $q->where('stage', $stage))
                    ->where('is_active', true)
                    ->latest();
            }
        ]);

        // Filter by period if provided
        if ($request->has('period')) {
            $query->whereHas('vacancyPeriod', function ($q) use ($request) {
                $q->where('period_id', $request->period);
            });
        }

        // Filter by company if provided
        if ($request->has('company')) {
            $query->whereHas('vacancyPeriod.vacancy', function ($q) use ($request) {
                $q->where('company_id', $request->company);
            });
        }

        // Get applications that should be in this stage
        if ($stage === 'psychological_test') {
            // For assessment/psychological_test stage, show ALL candidates who have been through this stage
            // regardless of whether they're still active or have been reviewed
            $query->whereHas('history', function ($q) use ($stage) {
                $q->whereHas('status', fn($sq) => $sq->where('stage', $stage));
                // Don't filter by is_active for psychological test - show all including reviewed ones
            });
        } else {
            // For other stages, only show active candidates
            $query->whereHas('history', function ($q) use ($stage) {
                $q->whereHas('status', fn($sq) => $sq->where('stage', $stage))
                    ->where('is_active', true);
            });
        }

        // Add stage-specific relations and data
        switch ($stage) {
            case 'psychological_test':
                $query->with(['userAnswers.question.choices']);
                break;
            case 'interview':
                $query->with(['history' => function ($q) {
                    $q->where('stage', 'interview')
                        ->with(['reviewer:id,name,email']);
                }]);
                break;
        }

        // Get candidate details
        $query->with([
            'user' => function ($q) {
                $q->with([
                    'candidatesProfile',
                    'candidatesEducations' => function ($q) {
                        $q->with('educationLevel:id,name');
                    },
                    'candidatesSkills:id,user_id,skill_name',
                    'candidatesLanguages:id,user_id,language_name',
                    'candidatesCertifications',
                    'candidatesWorkExperiences',
                    'candidatesOrganizations',
                    'candidatesCV' => function ($q) {
                        $q->latest();
                    }
                ]);
            }
        ]);

        $applications = $query->paginate(10)
            ->through(function ($application) use ($stage) {
                // Get current stage history (active history for this specific stage)
                $currentStageHistory = $application->history
                    ->filter(fn($h) => $h->status->stage === $stage && $h->is_active)
                    ->first();
                    
                // If no active history for current stage, try to find any history for this stage
                if (!$currentStageHistory) {
                    $currentStageHistory = $application->history
                        ->filter(fn($h) => $h->status->stage === $stage)
                        ->first();
                }
                
                $status = $currentStageHistory?->status;
                
                $baseData = [
                    'id' => $application->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'position' => $application->vacancyPeriod->vacancy->title,
                    'company' => $application->vacancyPeriod->vacancy->company->name,
                    'status' => $status ? $status->name : 'Pending',
                    'score' => $currentStageHistory?->score,
                    'scheduled_at' => $currentStageHistory?->scheduled_at,
                    'completed_at' => $currentStageHistory?->completed_at,
                    'notes' => $currentStageHistory?->notes,
                    'reviewed_by' => $currentStageHistory?->reviewer?->name,
                    // Add all history for UI display
                    'history' => $application->history->map(fn($history) => [
                        'id' => $history->id,
                        'stage' => $history->status->stage,
                        'status_name' => $history->status->name,
                        'status_code' => $history->status->code,
                        'score' => $history->score,
                        'notes' => $history->notes,
                        'processed_at' => $history->processed_at,
                        'completed_at' => $history->completed_at,
                        'reviewed_at' => $history->reviewed_at,
                        'reviewer_name' => $history->reviewer?->name,
                        'is_active' => $history->is_active,
                        'is_completed' => !is_null($history->completed_at),
                    ])->sortBy('processed_at')->values(),
                ];

                // Add candidate profile data
                if ($application->user->candidatesProfile) {
                    $baseData['candidate'] = [
                        'profile' => $application->user->candidatesProfile->toArray(),
                        'education' => $application->user->candidatesEducations->map(fn($edu) => [
                            'level' => $edu->educationLevel ? $edu->educationLevel->name : null,
                            'institution' => $edu->institution_name,
                            'faculty' => $edu->faculty,
                            'major' => $edu->major ? $edu->major->name : null,
                            'start_year' => $edu->year_in,
                            'end_year' => $edu->year_out,
                            'gpa' => $edu->gpa,
                        ])->toArray(),
                        'skills' => $application->user->candidatesSkills->pluck('skill_name')->toArray(),
                        'languages' => $application->user->candidatesLanguages->map(fn($lang) => [
                            'name' => $lang->language_name,
                            'level' => '', // Level is included in the language_name
                        ])->toArray(),
                        'certifications' => $application->user->candidatesCertifications->map(fn($cert) => [
                            'name' => $cert->name,
                            'issuer' => $cert->issuer,
                            'date' => $cert->date,
                        ])->toArray(),
                        'work_experiences' => $application->user->candidatesWorkExperiences->map(fn($exp) => [
                            'company' => $exp->company,
                            'position' => $exp->position,
                            'start_date' => $exp->start_date,
                            'end_date' => $exp->end_date,
                            'description' => $exp->description,
                        ])->toArray(),
                        'organizations' => $application->user->candidatesOrganizations->map(fn($org) => [
                            'name' => $org->name,
                            'position' => $org->position,
                            'start_year' => $org->start_year,
                            'end_year' => $org->end_year,
                        ])->toArray(),
                        'cv' => $application->user->candidatesCV?->path,
                    ];
                }

                // Add stage-specific data
                switch ($stage) {
                    case 'psychological_test':
                        if ($application->userAnswers->isNotEmpty()) {
                            $baseData['assessment'] = [
                                'answers' => $application->userAnswers->map(fn($answer) => [
                                    'question' => $answer->question->question_text,
                                    'answer' => $answer->choice?->choice_text ?? 'No answer selected',
                                    'is_correct' => $answer->choice?->is_correct ?? false,
                                    'score' => $answer->choice?->is_correct ? 100 : 0,
                                    'choices' => $answer->question->choices->map(fn($choice) => [
                                        'text' => $choice->choice_text,
                                        'is_correct' => $choice->is_correct,
                                    ])->toArray(),
                                ])->toArray(),
                                'total_score' => $application->userAnswers->avg('score'),
                            ];
                        }
                        break;

                    case 'interview':
                        if ($currentStageHistory) {
                            $baseData['interview'] = [
                                'interviewer' => $currentStageHistory->reviewer ? [
                                    'name' => $currentStageHistory->reviewer->name,
                                    'email' => $currentStageHistory->reviewer->email,
                                ] : null,
                                'feedback' => $currentStageHistory->notes,
                                'score' => $currentStageHistory->score,
                            ];
                        }
                        break;
                }

                return $baseData;
            });

        // Get period and company info if filters are provided
        $periodInfo = null;
        $companyInfo = null;

        if ($request->has('period') || $request->has('company')) {
            // Get the vacancy period that matches our filters
            $vacancyPeriodQuery = VacancyPeriods::query()
                ->with(['vacancy.company', 'period']);

            if ($request->has('period')) {
                $vacancyPeriodQuery->where('period_id', $request->query('period'));
            }

            if ($request->has('company')) {
                $vacancyPeriodQuery->whereHas('vacancy', function($q) use ($request) {
                    $q->where('company_id', $request->query('company'));
                });
            }

            $vacancyPeriod = $vacancyPeriodQuery->first();

            if ($vacancyPeriod) {
                if ($request->has('period')) {
                    $periodInfo = [
                        'name' => $vacancyPeriod->period->name,
                        'start_date' => $vacancyPeriod->period->start_time,
                        'end_date' => $vacancyPeriod->period->end_time,
                    ];
                }

                if ($request->has('company')) {
                    $companyInfo = [
                        'name' => $vacancyPeriod->vacancy->company->name,
                    ];
                }
            }
        }

        // Map stage to page name
        $pageMap = [
            'administrative_selection' => 'administration',
            'psychological_test' => 'assessment',
            'interview' => 'interview'
        ];

        $pageName = $pageMap[$stage] ?? 'administration';

        return Inertia::render("admin/company/{$pageName}", [
            'applications' => $applications,
            'period' => $periodInfo,
            'company' => $companyInfo,
            'filters' => $request->only(['search', 'status']),
            'stage' => [
                'current' => $stage,
                'name' => $status->name,
                'description' => $status->description,
            ],
        ]);
    }

    /**
     * Update application stage status
     */
    public function update(Request $request, Application $application, string $stage)
    {
        try {
            DB::beginTransaction();

            // Define validation rules based on stage and status
            $rules = [
                'status' => 'required|string|in:passed,rejected',
                'notes' => $request->input('status') === 'rejected' ? 'required|string' : 'nullable|string',
                'score' => ($stage === 'administration' || $stage === 'interview') && $request->input('status') === 'passed' 
                    ? 'required|numeric|min:10|max:99' 
                    : 'nullable|numeric',
                'zoom_url' => 'nullable|url',
                'scheduled_at' => 'nullable|date',
            ];

            $validated = $request->validate($rules);

            // Get current stage status
            $currentStageStatus = $this->getCurrentStageStatus($stage);
            if (!$currentStageStatus) {
                throw new \Exception("Invalid stage: {$stage}");
            }

            // Get current active history for this stage
            $currentHistory = $application->history()
                ->where('status_id', $currentStageStatus->id)
                ->where('is_active', true)
                ->first();

            if (!$currentHistory) {
                throw new \Exception("No active history found for this stage");
            }

            if ($validated['status'] === 'passed') {
                // Calculate score based on stage
                $calculatedScore = $validated['score'] ?? null;
                
                if ($stage === 'psychological_test') {
                    // Load vacancy relation if not already loaded
                    $application->load('vacancyPeriod.vacancy.questionPack');
                    
                    // For psychological test, use the new scoring logic
                    $calculatedScore = $this->calculateTestScore($application);
                    
                    // If psychological test returns null, it means manual scoring is required
                    if ($calculatedScore === null && !isset($validated['score'])) {
                        // Don't proceed with automatic passing - require manual scoring
                        return back()->withErrors(['error' => 'This is a psychological test that requires manual scoring. Please use the export feature to manually evaluate and score the test.']);
                    }
                    
                    // Use manual score if provided, otherwise use calculated score
                    $calculatedScore = $validated['score'] ?? $calculatedScore ?? 0;
                }

                // Update current history with score and notes
                $currentHistory->update([
                    'score' => $calculatedScore,
                    'notes' => $validated['notes'] ?? null,
                    'completed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                // Move to next stage
                $nextStageStatus = $this->getNextStageStatus($stage);
                if ($nextStageStatus) {
                    // Deactivate all previous active histories EXCEPT current one that we just updated
                    $application->history()
                        ->where('is_active', true)
                        ->where('id', '!=', $currentHistory->id)
                        ->update(['is_active' => false]);

                    // Now deactivate current history (preserving the score we just set)
                    $currentHistory->update(['is_active' => false]);

                    // Update application status to next stage
                    $application->update(['status_id' => $nextStageStatus->id]);

                    // Create new history for next stage
                    $nextHistoryData = [
                        'application_id' => $application->id,
                        'status_id' => $nextStageStatus->id,
                        'processed_at' => now(),
                        'is_active' => true,
                    ];

                    // Add interview scheduling data if moving from assessment to interview
                    if ($stage === 'psychological_test' && isset($validated['zoom_url']) && isset($validated['scheduled_at'])) {
                        $nextHistoryData['scheduled_at'] = $validated['scheduled_at'];
                        $nextHistoryData['resource_url'] = $validated['zoom_url']; // Store in resource_url column
                        $nextHistoryData['notes'] = $validated['notes'] ?? null;
                    }

                    ApplicationHistory::create($nextHistoryData);
                } else {
                    // This is the final stage - mark as completed
                    $completedStatus = Status::where('code', 'completed')->first();
                    if ($completedStatus) {
                        $application->update(['status_id' => $completedStatus->id]);
                    }
                }

            } else if ($validated['status'] === 'rejected') {
                // Update current history with rejection notes
                $currentHistory->update([
                    'notes' => $validated['notes'],
                    'completed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'is_active' => false, // Deactivate since rejected
                ]);

                // Update application status to rejected
                $rejectedStatus = Status::where('code', 'rejected')->first();
                if ($rejectedStatus) {
                    $application->update(['status_id' => $rejectedStatus->id]);
                }
            }

            DB::commit();
            return back()->with('success', 'Application updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Application stage update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update application: ' . $e->getMessage()]);
        }
    }

    /**
     * Get current stage status
     */
    private function getCurrentStageStatus(string $stage): ?Status
    {
        $stageCodeMap = [
            'administration' => 'admin_selection',
            'administrative_selection' => 'admin_selection',
            'psychological_test' => 'psychotest', 
            'interview' => 'interview'
        ];

        $code = $stageCodeMap[$stage] ?? null;
        if (!$code) return null;

        return Status::where('code', $code)->first();
    }

    /**
     * Get next stage status
     */
    private function getNextStageStatus(string $currentStage): ?Status
    {
        $nextStageMap = [
            'administration' => 'psychotest',
            'administrative_selection' => 'psychotest',
            'psychological_test' => 'interview',
            'interview' => null // Final stage
        ];

        $nextCode = $nextStageMap[$currentStage] ?? null;
        if (!$nextCode) return null;

        return Status::where('code', $nextCode)->first();
    }

    public function administration(Request $request): Response
    {
        // Get the administration status
        $administrationStatus = Status::where('code', 'admin_selection')
            ->first();
        
        if (!$administrationStatus) {
            return Inertia::render('admin/company/administration', [
                'candidates' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 10,
                    'last_page' => 1,
                    'total' => 0,
                ],
                'message' => 'Administration status not found'
            ]);
        }

        // Build the base query
        $query = Application::with([
            'user:id,name,email',
            'vacancyPeriod' => function($query) {
                $query->select('id', 'vacancy_id', 'period_id')
                    ->with(['vacancy:id,title,company_id', 'period:id,name,start_time,end_time']);
            },
            // Load all history, not just active ones
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->latest();
            }
        ])
        // Show applications that have been through administration stage (not just currently in it)
        ->whereHas('history', function($query) use ($administrationStatus) {
            $query->where('status_id', $administrationStatus->id);
        });

        // Filter by company if provided
        if ($request->has('company')) {
            $companyId = $request->query('company');
            $query->whereHas('vacancyPeriod.vacancy', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Filter by period if provided
        if ($request->has('period')) {
            $periodId = $request->query('period');
            $query->whereHas('vacancyPeriod', function($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        // Get paginated results
        $applications = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Transform the data to include position and all history
        $transformedData = collect($applications->items())->map(function ($application) {
            // Get administration history specifically for score and reviewer
            $adminHistory = $application->history->filter(function ($history) {
                return $history->status && $history->status->stage === 'administrative_selection';
            })->first();
            
            $data = [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                ],
                'vacancy_period' => [
                    'vacancy' => [
                        'title' => $application->vacancyPeriod->vacancy->title ?? 'N/A'
                    ]
                ],
                'created_at' => $application->created_at,
                // Add direct score and reviewer for easy access
                'score' => $adminHistory?->score,
                'reviewed_by' => $adminHistory?->reviewer?->name,
                'completed_at' => $adminHistory?->completed_at,
                // Add all history for UI display
                'history' => $application->history->map(fn($history) => [
                    'id' => $history->id,
                    'stage' => $history->status->stage,
                    'status_name' => $history->status->name,
                    'status_code' => $history->status->code,
                    'score' => $history->score,
                    'notes' => $history->notes,
                    'processed_at' => $history->processed_at,
                    'completed_at' => $history->completed_at,
                    'reviewed_at' => $history->reviewed_at,
                    'reviewer_name' => $history->reviewer?->name,
                    'is_active' => $history->is_active,
                    'is_completed' => !is_null($history->completed_at),
                ])->sortBy('processed_at')->values(),
            ];
            return $data;
        })->all();

        // Get period and company info if filters are provided
        $periodInfo = null;
        $companyInfo = null;

        if ($request->has('period') || $request->has('company')) {
            // Get the vacancy period that matches our filters
            $vacancyPeriodQuery = VacancyPeriods::query()
                ->with(['vacancy.company', 'period']);

            if ($request->has('period')) {
                $vacancyPeriodQuery->where('period_id', $request->query('period'));
            }

            if ($request->has('company')) {
                $vacancyPeriodQuery->whereHas('vacancy', function($q) use ($request) {
                    $q->where('company_id', $request->query('company'));
                });
            }

            $vacancyPeriod = $vacancyPeriodQuery->first();

            if ($vacancyPeriod) {
                if ($request->has('period')) {
                    $periodInfo = [
                        'name' => $vacancyPeriod->period->name,
                        'start_date' => $vacancyPeriod->period->start_time,
                        'end_date' => $vacancyPeriod->period->end_time,
                    ];
                }

                if ($request->has('company')) {
                    $companyInfo = [
                        'name' => $vacancyPeriod->vacancy->company->name,
                    ];
                }
            }
        }

        return Inertia::render('admin/company/administration', [
            'candidates' => [
                'data' => $transformedData,
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'last_page' => $applications->lastPage(),
                'total' => $applications->total(),
                'links' => $applications->linkCollection()->toArray()
            ],
            'filters' => [
                'company' => $request->query('company'),
                'period' => $request->query('period'),
            ],
            'periodInfo' => $periodInfo ? [
                'name' => $periodInfo['name'],
                'start_date' => $periodInfo['start_date'],
                'end_date' => $periodInfo['end_date'],
            ] : null,
            'companyInfo' => $companyInfo ? [
                'name' => $companyInfo['name'],
            ] : null,
        ]);
    }

    public function administrationDetail($id): Response
    {
        $application = Application::with([
            'user.candidatesProfile',
            'user.candidatesEducations.educationLevel',
            'user.candidatesWorkExperiences',
            'user.candidatesSkills',
            'user.candidatesLanguages',
            'user.candidatesCourses',
            'user.candidatesCertifications',
            'user.candidatesOrganizations',
            'user.candidatesAchievements',
            'user.candidatesSocialMedia',
            'user.candidatesCV',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'status',
            'history' => function($query) {
                $query->with(['status', 'reviewer'])->latest();
            }
        ])->findOrFail($id);

        $profile = $application->user->candidatesProfile;
        $profileData = $profile ? [
            'full_name' => $profile->full_name,
            'phone' => $profile->phone_number,
            'address' => $profile->address,
            'birth_place' => $profile->place_of_birth,
            'birth_date' => $profile->date_of_birth,
            'gender' => $profile->gender,
        ] : null;

        return Inertia::render('admin/company/administration-detail', [
            'candidate' => [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'profile' => $profileData,
                    'education' => $application->user->candidatesEducations->map(fn($edu) => [
                        'level' => $edu->educationLevel ? $edu->educationLevel->name : null,
                        'institution' => $edu->institution_name,
                        'faculty' => $edu->faculty,
                        'major' => $edu->major ? $edu->major->name : null,
                        'start_year' => $edu->year_in,
                        'end_year' => $edu->year_out,
                        'gpa' => $edu->gpa,
                    ]),
                    'work_experiences' => $application->user->candidatesWorkExperiences->map(fn($exp) => [
                        'company' => $exp->company,
                        'position' => $exp->position,
                        'start_date' => $exp->start_date,
                        'end_date' => $exp->end_date,
                        'description' => $exp->description,
                    ]),
                    'skills' => $application->user->candidatesSkills->map(fn($skill) => [
                        'name' => $skill->skill_name,
                        'level' => $skill->proficiency_level,
                    ]),
                    'languages' => $application->user->candidatesLanguages->map(fn($lang) => [
                        'name' => $lang->language_name,
                        'proficiency' => $lang->proficiency_level,
                    ]),
                    'courses' => $application->user->candidatesCourses->map(fn($course) => [
                        'name' => $course->name,
                        'institution' => $course->institution,
                        'completion_date' => $course->completion_date,
                        'description' => $course->description,
                    ]),
                    'certifications' => $application->user->candidatesCertifications->map(fn($cert) => [
                        'name' => $cert->name,
                        'issuer' => $cert->issuer,
                        'date' => $cert->date,
                        'expiry_date' => $cert->expiry_date,
                        'credential_id' => $cert->credential_id,
                    ]),
                    'organizations' => $application->user->candidatesOrganizations->map(fn($org) => [
                        'name' => $org->name,
                        'position' => $org->position,
                        'start_year' => $org->start_year,
                        'end_year' => $org->end_year,
                        'description' => $org->description,
                    ]),
                    'achievements' => $application->user->candidatesAchievements->map(fn($achievement) => [
                        'title' => $achievement->title,
                        'issuer' => $achievement->issuer,
                        'date' => $achievement->date,
                        'description' => $achievement->description,
                    ]),
                    'social_media' => $application->user->candidatesSocialMedia->map(fn($social) => [
                        'platform' => $social->platform,
                        'url' => $social->url,
                    ]),
                    'cv' => $application->user->candidatesCV ? [
                        'path' => $application->user->candidatesCV->path,
                        'uploaded_at' => $application->user->candidatesCV->created_at,
                    ] : null,
                ],
                'vacancy' => [
                    'id' => $application->vacancyPeriod->vacancy->id,
                    'title' => $application->vacancyPeriod->vacancy->title,
                    'company' => [
                        'id' => $application->vacancyPeriod->vacancy->company->id,
                        'name' => $application->vacancyPeriod->vacancy->company->name,
                    ],
                    'period' => [
                        'id' => $application->vacancyPeriod->period->id,
                        'name' => $application->vacancyPeriod->period->name,
                        'start_time' => $application->vacancyPeriod->period->start_time,
                        'end_time' => $application->vacancyPeriod->period->end_time,
                    ],
                ],
                'status' => [
                    'id' => $application->status->id,
                    'name' => $application->status->name,
                    'code' => $application->status->code,
                ],
                'history' => $application->history->map(fn($history) => [
                    'id' => $history->id,
                    'status' => [
                        'name' => $history->status->name,
                        'code' => $history->status->code,
                    ],
                    'notes' => $history->notes,
                    'score' => $history->score,
                    'processed_at' => $history->processed_at,
                    'scheduled_at' => $history->scheduled_at,
                    'completed_at' => $history->completed_at,
                    'reviewer' => $history->reviewer ? [
                        'id' => $history->reviewer->id,
                        'name' => $history->reviewer->name,
                        'email' => $history->reviewer->email,
                    ] : null,
                ]),
                'applied_at' => $application->created_at,
            ]
        ]);
    }

    public function assessment(Request $request): Response
    {
        // Get the assessment status
        $assessmentStatus = Status::where('code', 'psychotest')
            ->where('stage', 'psychological_test')
            ->first();
        
        if (!$assessmentStatus) {
            return Inertia::render('admin/company/assessment', [
                'candidates' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 10,
                    'last_page' => 1,
                    'total' => 0,
                ],
                'message' => 'Assessment status not found',
                'filters' => [
                    'company' => $request->query('company'),
                    'period' => $request->query('period'),
                ],
            ]);
        }

        // Build the base query
        $query = Application::with([
            'user:id,name,email',
            'vacancyPeriod' => function($query) {
                $query->select('id', 'vacancy_id', 'period_id')
                    ->with(['vacancy:id,title,company_id,question_pack_id', 'period:id,name,start_time,end_time'])
                    ->with(['vacancy.questionPack:id,test_type,pack_name']); // Add questionPack relation
            },
            'userAnswers' => function($query) {
                $query->with([
                    'question:id,question_text',
                    'choice:id,choice_text,is_correct'
                ]);
            },
            // Load all history, not just active assessment history
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->latest();
            }
        ])
        // Show applications in assessment stage OR those who have been through assessment
        ->where(function($query) use ($assessmentStatus) {
            $query->where('status_id', $assessmentStatus->id)
                  ->orWhereHas('history', function($q) use ($assessmentStatus) {
                      $q->where('status_id', $assessmentStatus->id);
                  });
        });

        // Filter by company if provided
        if ($request->has('company')) {
            $companyId = $request->query('company');
            $query->whereHas('vacancyPeriod.vacancy', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Filter by period if provided
        if ($request->has('period')) {
            $periodId = $request->query('period');
            $query->whereHas('vacancyPeriod', function($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        // Get paginated results
        $applications = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Transform the data
        $transformedData = collect($applications->items())->map(function ($application) {
            $currentHistory = $application->history->first();
            return [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                ],
                'vacancy_period' => [
                    'vacancy' => [
                        'title' => $application->vacancyPeriod->vacancy->title ?? 'N/A',
                        'question_pack' => $application->vacancyPeriod->vacancy->questionPack ? [
                            'test_type' => $application->vacancyPeriod->vacancy->questionPack->test_type,
                            'pack_name' => $application->vacancyPeriod->vacancy->questionPack->pack_name,
                        ] : null,
                    ]
                ],
                'created_at' => $application->created_at,
                'history' => $application->history->map(function($history) {
                    return [
                        'id' => $history->id,
                        'stage' => $history->status->stage ?? null,
                        'status_name' => $history->status->name ?? null,
                        'status_code' => $history->status->code ?? null,
                        'processed_at' => $history->processed_at,
                        'completed_at' => $history->completed_at,
                        'score' => $history->score,
                        'notes' => $history->notes,
                        'reviewer_name' => $history->reviewer?->name,
                        'reviewed_by' => $history->reviewer?->name,
                        'is_active' => $history->is_active,
                        'is_completed' => $history->is_completed,
                    ];
                }),
                'stages' => [
                    'psychological_test' => [
                        'score' => $currentHistory?->score,
                    ]
                ],
                'assessment' => [
                    'answers' => $application->userAnswers->map(fn($answer) => [
                        'question' => $answer->question->question_text,
                        'answer' => $answer->choice?->choice_text ?? 'No answer selected',
                        'is_correct' => $answer->choice?->is_correct ?? false,
                        'score' => $answer->choice?->is_correct ? 100 : 0,
                    ])->toArray(),
                    'total_score' => $application->userAnswers->count() > 0 
                        ? $application->userAnswers->filter(function($answer) {
                            return $answer->choice?->is_correct ?? false;
                        })->count() / $application->userAnswers->count() * 100
                        : 0,
                ]
            ];
        })->all();

        // Get period and company info
        $periodInfo = null;
        $companyInfo = null;

        if ($request->has('period') || $request->has('company')) {
            $vacancyPeriodQuery = VacancyPeriods::query()
                ->with(['vacancy.company', 'period']);

            if ($request->has('period')) {
                $vacancyPeriodQuery->where('period_id', $request->query('period'));
            }

            if ($request->has('company')) {
                $vacancyPeriodQuery->whereHas('vacancy', function($q) use ($request) {
                    $q->where('company_id', $request->query('company'));
                });
            }

            $vacancyPeriod = $vacancyPeriodQuery->first();

            if ($vacancyPeriod) {
                if ($request->has('period')) {
                    $periodInfo = [
                        'name' => $vacancyPeriod->period->name,
                        'start_date' => $vacancyPeriod->period->start_time,
                        'end_date' => $vacancyPeriod->period->end_time,
                    ];
                }

                if ($request->has('company')) {
                    $companyInfo = [
                        'name' => $vacancyPeriod->vacancy->company->name,
                    ];
                }
            }
        }

        return Inertia::render('admin/company/assessment', [
            'candidates' => [
                'data' => $transformedData,
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'last_page' => $applications->lastPage(),
                'total' => $applications->total(),
                'links' => $applications->linkCollection()->toArray()
            ],
            'filters' => [
                'company' => $request->query('company'),
                'period' => $request->query('period'),
            ],
            'periodInfo' => $periodInfo,
            'companyInfo' => $companyInfo,
            'stage' => [
                'current' => 'psychological_test',
                'name' => $assessmentStatus->name,
                'description' => $assessmentStatus->description,
            ],
        ]);
    }

    public function interview(Request $request): Response
    {
        // Get the interview status
        $interviewStatus = Status::where('code', 'interview')
            ->where('stage', 'interview')
            ->first();
        
        if (!$interviewStatus) {
            return Inertia::render('admin/company/interview', [
                'candidates' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 10,
                    'last_page' => 1,
                    'total' => 0,
                ],
                'message' => 'Interview status not found',
                'filters' => [
                    'company' => $request->query('company'),
                    'period' => $request->query('period'),
                ],
            ]);
        }

        // Build the base query
        $query = Application::with([
            'user:id,name,email',
            'vacancyPeriod' => function($query) {
                $query->select('id', 'vacancy_id', 'period_id')
                    ->with(['vacancy:id,title,company_id', 'period:id,name,start_time,end_time']);
            },
            // Load all history, not just active interview history
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->latest();
            }
        ])
        // Show applications in interview stage OR those who have been through interview
        ->where(function($query) use ($interviewStatus) {
            $query->where('status_id', $interviewStatus->id)
                  ->orWhereHas('history', function($q) use ($interviewStatus) {
                      $q->where('status_id', $interviewStatus->id);
                  });
        });

        // Filter by company if provided
        if ($request->has('company')) {
            $companyId = $request->query('company');
            $query->whereHas('vacancyPeriod.vacancy', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Filter by period if provided
        if ($request->has('period')) {
            $periodId = $request->query('period');
            $query->whereHas('vacancyPeriod', function($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        // Get paginated results
        $applications = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Transform the data
        $transformedData = collect($applications->items())->map(function ($application) {
            $currentHistory = $application->history->first();
            
            return [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                ],
                'vacancy_period' => [
                    'vacancy' => [
                        'title' => $application->vacancyPeriod->vacancy->title ?? 'N/A'
                    ]
                ],
                'created_at' => $application->created_at,
                'stages' => [
                    'interview' => [
                        'status' => $currentHistory?->status->code ?? 'pending',
                        'scheduled_at' => $currentHistory?->scheduled_at ? $currentHistory->scheduled_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                        'completed_at' => $currentHistory?->completed_at ? $currentHistory->completed_at->toISOString() : null,
                        'notes' => $currentHistory?->notes,
                        // Only show interview score if interview has been completed
                        'score' => ($currentHistory && $currentHistory->completed_at && $currentHistory->score) 
                            ? number_format($currentHistory->score, 2) 
                            : null,
                        'interviewer' => $currentHistory?->reviewer ? [
                            'name' => $currentHistory->reviewer->name,
                            'email' => $currentHistory->reviewer->email,
                        ] : null,
                    ]
                ]
            ];
        })->all();

        // Get period and company info
        $periodInfo = null;
        $companyInfo = null;

        if ($request->has('period') || $request->has('company')) {
            $vacancyPeriodQuery = VacancyPeriods::query()
                ->with(['vacancy.company', 'period']);

            if ($request->has('period')) {
                $vacancyPeriodQuery->where('period_id', $request->query('period'));
            }

            if ($request->has('company')) {
                $vacancyPeriodQuery->whereHas('vacancy', function($q) use ($request) {
                    $q->where('company_id', $request->query('company'));
                });
            }

            $vacancyPeriod = $vacancyPeriodQuery->first();

            if ($vacancyPeriod) {
                if ($request->has('period')) {
                    $periodInfo = [
                        'name' => $vacancyPeriod->period->name,
                        'start_date' => $vacancyPeriod->period->start_time,
                        'end_date' => $vacancyPeriod->period->end_time,
                    ];
                }

                if ($request->has('company')) {
                    $companyInfo = [
                        'name' => $vacancyPeriod->vacancy->company->name,
                    ];
                }
            }
        }

        return Inertia::render('admin/company/interview', [
            'candidates' => [
                'data' => $transformedData,
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'last_page' => $applications->lastPage(),
                'total' => $applications->total(),
                'links' => $applications->linkCollection()->toArray()
            ],
            'filters' => [
                'company' => $request->query('company'),
                'period' => $request->query('period'),
            ],
            'periodInfo' => $periodInfo,
            'companyInfo' => $companyInfo,
            'stage' => [
                'current' => 'interview',
                'name' => $interviewStatus->name,
                'description' => $interviewStatus->description,
            ],
        ]);
    }

    public function interviewDetail($id): Response
    {
        $application = Application::with([
            'user.candidatesProfile',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'history' => function($query) {
                $query->whereHas('status', function($q) {
                    $q->where('stage', 'interview');
                })
                ->where('is_active', true)
                ->with(['status', 'reviewer'])
                ->latest();
            },
            'userAnswers' => function($query) {
                $query->with(['question', 'choice']);
            }
        ])->findOrFail($id);

        $currentHistory = $application->history->first();

        // Calculate assessment result
        $assessmentScore = $application->userAnswers->count() > 0 
            ? $application->userAnswers->filter(function($answer) {
                return $answer->choice?->is_correct ?? false;
            })->count() / $application->userAnswers->count() * 100
            : 0;

        $lastAssessmentHistory = ApplicationHistory::where('application_id', $id)
            ->whereHas('status', function($q) {
                $q->where('stage', 'psychological_test');
            })
            ->where('is_active', true)
            ->first();

        return Inertia::render('admin/company/interview-detail', [
            'candidate' => [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'cv' => $application->user->candidatesCV ? [
                        'path' => $application->user->candidatesCV->cv_path,
                        'filename' => $application->user->candidatesCV->cv_filename,
                    ] : null,
                    'profile' => $application->user->candidatesProfile ? [
                        'full_name' => $application->user->candidatesProfile->full_name,
                        'phone' => $application->user->candidatesProfile->phone_number,
                        'address' => $application->user->candidatesProfile->address,
                        'birth_place' => $application->user->candidatesProfile->place_of_birth,
                        'birth_date' => $application->user->candidatesProfile->date_of_birth,
                        'gender' => $application->user->candidatesProfile->gender,
                    ] : null,
                ],
                'vacancy' => [
                    'id' => $application->vacancyPeriod->vacancy->id,
                    'title' => $application->vacancyPeriod->vacancy->title,
                    'company' => [
                        'id' => $application->vacancyPeriod->vacancy->company->id,
                        'name' => $application->vacancyPeriod->vacancy->company->name,
                    ],
                    'period' => [
                        'id' => $application->vacancyPeriod->period->id,
                        'name' => $application->vacancyPeriod->period->name,
                        'start_time' => $application->vacancyPeriod->period->start_time,
                        'end_time' => $application->vacancyPeriod->period->end_time,
                    ],
                ],
                'stages' => [
                    'interview' => [
                        'scheduled_at' => $currentHistory?->scheduled_at ? $currentHistory->scheduled_at->format('Y-m-d H:i:s') : null,
                        'completed_at' => $currentHistory?->completed_at ? $currentHistory->completed_at->format('Y-m-d H:i:s') : null,
                        'score' => $currentHistory?->score,
                        'notes' => $currentHistory?->notes,
                        'duration' => $currentHistory?->scheduled_at && $currentHistory?->completed_at ? 
                            $this->calculateDuration($currentHistory->scheduled_at, $currentHistory->completed_at) : '60 Menit',
                        'location' => $currentHistory?->resource_url ? 'Online via Web' : 'Kantor Pusat',
                        'interviewer' => $currentHistory?->reviewer ? [
                            'name' => $currentHistory->reviewer->name,
                            'email' => $currentHistory->reviewer->email,
                        ] : null,
                    ],
                ],
                'assessment_result' => $lastAssessmentHistory ? [
                    'total_score' => $assessmentScore,
                    'completed_at' => $lastAssessmentHistory->completed_at,
                ] : null,
            ],
        ]);
    }

    public function assessmentDetail($id): Response
    {
        $application = Application::with([
            'user.candidatesProfile',
            'user.candidatesCV',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.vacancy.questionPack', // Add this relation
            'vacancyPeriod.period',
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->orderBy('processed_at', 'asc');
            },
            'userAnswers' => function($query) {
                $query->with(['question.choices', 'choice']);
            }
        ])->findOrFail($id);

        $currentHistory = $application->history->whereIn('status.stage', ['psychological_test'])->first();
        $firstHistory = $application->history->first(); // Get the very first history

        // Calculate assessment score
        $assessmentScore = $application->userAnswers->count() > 0 
            ? $application->userAnswers->filter(function($answer) {
                return $answer->choice?->is_correct ?? false;
            })->count() / $application->userAnswers->count() * 100
            : 0;

        // Debug: Log the data to see what's being sent
        Log::info('Assessment Detail Data:', [
            'application_id' => $application->id,
            'user_answers_count' => $application->userAnswers->count(),
            'assessment_score' => $assessmentScore,
            'user_answers' => $application->userAnswers->map(function($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text,
                    'choice_text' => $answer->choice?->choice_text ?? 'No answer selected',
                    'is_correct' => $answer->choice?->is_correct ?? false,
                ];
            })
        ]);

        return Inertia::render('admin/company/assessment-detail', [
            'candidate' => [
                'id' => $application->id,
                'application_started_at' => $firstHistory?->processed_at ?? $application->created_at,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'cv' => $application->user->candidatesCV ? [
                        'path' => $application->user->candidatesCV->path,
                    ] : null,
                    'profile' => $application->user->candidatesProfile ? [
                        'full_name' => $application->user->candidatesProfile->full_name,
                        'phone' => $application->user->candidatesProfile->phone_number,
                        'address' => $application->user->candidatesProfile->address,
                        'birth_place' => $application->user->candidatesProfile->place_of_birth,
                        'birth_date' => $application->user->candidatesProfile->date_of_birth,
                        'gender' => $application->user->candidatesProfile->gender,
                    ] : null,
                ],
                'vacancy' => [
                    'id' => $application->vacancyPeriod->vacancy->id,
                    'title' => $application->vacancyPeriod->vacancy->title,
                    'company' => [
                        'id' => $application->vacancyPeriod->vacancy->company->id,
                        'name' => $application->vacancyPeriod->vacancy->company->name,
                    ],
                    'period' => [
                        'id' => $application->vacancyPeriod->period->id,
                        'name' => $application->vacancyPeriod->period->name,
                        'start_time' => $application->vacancyPeriod->period->start_time,
                        'end_time' => $application->vacancyPeriod->period->end_time,
                    ],
                    'question_pack' => $application->vacancyPeriod->vacancy->questionPack ? [
                        'test_type' => $application->vacancyPeriod->vacancy->questionPack->test_type,
                        'pack_name' => $application->vacancyPeriod->vacancy->questionPack->pack_name,
                    ] : null,
                ],
                'history' => $application->history->map(function($history) {
                    return [
                        'id' => $history->id,
                        'status' => [
                            'name' => $history->status->name,
                            'code' => $history->status->code,
                        ],
                        'notes' => $history->notes,
                        'score' => $history->score,
                        'processed_at' => $history->processed_at,
                        'scheduled_at' => $history->scheduled_at,
                        'completed_at' => $history->completed_at,
                        'reviewer' => $history->reviewer ? [
                            'id' => $history->reviewer->id,
                            'name' => $history->reviewer->name,
                            'email' => $history->reviewer->email,
                        ] : null,
                    ];
                }),
                'stages' => [
                    'psychological_test' => [
                        'status' => $currentHistory?->status->code ?? 'pending',
                        'started_at' => $currentHistory?->processed_at,
                        'completed_at' => $currentHistory?->completed_at,
                        'score' => $assessmentScore,
                        'answers' => $application->userAnswers->count() > 0 
                            ? $application->userAnswers->map(fn($answer) => [
                                'question' => [
                                    'text' => $answer->question->question_text,
                                    'choices' => $answer->question->choices->map(fn($choice) => [
                                        'text' => $choice->choice_text,
                                        'is_correct' => $choice->is_correct,
                                    ]),
                                ],
                                'selected_answer' => [
                                    'text' => $answer->choice?->choice_text ?? 'No answer selected',
                                    'is_correct' => $answer->choice?->is_correct ?? false,
                                ],
                            ])
                            : [
                                [
                                    'question' => [
                                        'text' => 'No questions answered yet',
                                        'choices' => [],
                                    ],
                                    'selected_answer' => [
                                        'text' => 'No answer provided',
                                        'is_correct' => false,
                                    ],
                                ]
                            ],
                    ],
                ],
            ],
        ]);
    }

    public function reports(Request $request): Response
    {
        try {
            // Build the base query - get applications that have completed all 3 stages and have reports
            $query = Application::with([
                'user:id,name,email',
                'vacancyPeriod.vacancy.company',
                'status',
                'history' => function($query) {
                    $query->with(['status', 'reviewer'])
                        ->latest();
                },
                'report'
            ])
            // Only get applications that have reports with all 3 scores (pending, accepted, or rejected)
            ->whereHas('report', function($q) {
                $q->whereIn('final_decision', ['pending', 'accepted', 'rejected'])
                  ->whereNotNull('overall_score');
            })
            // Ensure they have history records for all 3 stages with scores
            ->whereHas('history', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->where('code', 'admin_selection');
                })->whereNotNull('score');
            })
            ->whereHas('history', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->where('code', 'psychotest');
                })->whereNotNull('score');
            })
            ->whereHas('history', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->where('code', 'interview');
                })->whereNotNull('score');
            });

            // Filter by company if provided
            if ($request->has('company')) {
                $query->whereHas('vacancyPeriod.vacancy', function($q) use ($request) {
                    $q->where('company_id', $request->company);
                });
            }

            // Filter by period if provided
            if ($request->has('period')) {
                $query->whereHas('vacancyPeriod', function($q) use ($request) {
                    $q->where('period_id', $request->period);
                });
            }

            // Get paginated results
            $applications = $query->paginate(10)->withQueryString();

            // Transform the data
            $transformedData = $applications->through(function ($application) {
                try {
                    // Get all histories for this application (including inactive ones for scores)
                    $allHistories = $application->history;
                    
                    // Get specific stage histories (these might be inactive but have scores)
                    $administrationHistory = $allHistories->first(function($history) {
                        return $history->status->code === 'admin_selection' && !is_null($history->score);
                    });

                    $assessmentHistory = $allHistories->first(function($history) {
                        return $history->status->code === 'psychotest' && !is_null($history->score);
                    });

                    $interviewHistory = $allHistories->first(function($history) {
                        return $history->status->code === 'interview' && !is_null($history->score);
                    });

                    // Transform scores with type casting
                    $scores = [
                        'administration' => $administrationHistory?->score ? (float)$administrationHistory->score : null,
                        'assessment' => $assessmentHistory?->score ? (float)$assessmentHistory->score : null,
                        'interview' => $interviewHistory?->score ? (float)$interviewHistory->score : null,
                        'average' => $application->report?->overall_score ? (float)$application->report->overall_score : null
                    ];

                    return [
                        'id' => $application->id,
                        'user' => [
                            'name' => $application->user->name ?? 'Unknown',
                            'email' => $application->user->email ?? 'unknown@example.com',
                        ],
                        'vacancy_period' => [
                            'vacancy' => [
                                'title' => $application->vacancyPeriod->vacancy->title ?? 'Unknown Position'
                            ]
                        ],
                        'scores' => $scores,
                        'status' => [
                            'name' => $application->status->name ?? 'Unknown',
                            'code' => $application->status->code ?? 'unknown',
                        ],
                        'final_decision' => [
                            'status' => $application->report?->final_decision ?? 'pending',
                            'notes' => $application->report?->final_notes,
                            'decided_by' => $application->report?->decisionMaker?->name ?? null,
                            'decided_at' => $application->report?->decision_made_at,
                        ]
                    ];
                } catch (\Exception $e) {
                    Log::error('Error transforming application data:', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);

                    // Return safe default data
                    return [
                        'id' => $application->id,
                        'user' => [
                            'name' => 'Error loading data',
                            'email' => 'error@example.com',
                        ],
                        'vacancy_period' => [
                            'vacancy' => [
                                'title' => 'Error loading position'
                            ]
                        ],
                        'scores' => [
                            'administration' => null,
                            'assessment' => null,
                            'interview' => null,
                            'average' => null
                        ],
                        'status' => [
                            'name' => 'Error',
                            'code' => 'error',
                        ],
                        'final_decision' => [
                            'status' => 'pending',
                            'notes' => null,
                            'decided_by' => null,
                            'decided_at' => null,
                        ]
                    ];
                }
            });

            // Get period and company info if filters are provided
            $periodInfo = null;
            $companyInfo = null;

            if ($request->has('period') || $request->has('company')) {
                $vacancyPeriodQuery = VacancyPeriods::query()
                    ->with(['vacancy.company', 'period']);

                if ($request->has('period')) {
                    $vacancyPeriodQuery->where('period_id', $request->query('period'));
                }

                if ($request->has('company')) {
                    $vacancyPeriodQuery->whereHas('vacancy', function($q) use ($request) {
                        $q->where('company_id', $request->query('company'));
                    });
                }

                $vacancyPeriod = $vacancyPeriodQuery->first();

                if ($vacancyPeriod) {
                    if ($request->has('period')) {
                        $periodInfo = [
                            'name' => $vacancyPeriod->period->name,
                            'start_date' => $vacancyPeriod->period->start_time,
                            'end_date' => $vacancyPeriod->period->end_time,
                        ];
                    }

                    if ($request->has('company')) {
                        $companyInfo = [
                            'name' => $vacancyPeriod->vacancy->company->name,
                        ];
                    }
                }
            }

            return Inertia::render('admin/company/reports', [
                'candidates' => [
                    'data' => $transformedData,
                    'current_page' => $applications->currentPage(),
                    'per_page' => $applications->perPage(),
                    'last_page' => $applications->lastPage(),
                    'total' => $applications->total(),
                ],
                'filters' => [
                    'company' => $request->query('company'),
                    'period' => $request->query('period'),
                    'sort' => $sortColumn ?? 'created_at',
                    'order' => $sortOrder ?? 'desc',
                ],
                'periodInfo' => $periodInfo,
                'companyInfo' => $companyInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in reports method:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('admin/company/reports', [
                'candidates' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 10,
                    'last_page' => 1,
                    'total' => 0,
                ],
                'filters' => [
                    'company' => $request->query('company'),
                    'period' => $request->query('period'),
                    'sort' => 'created_at',
                    'order' => 'desc',
                ],
                'error' => 'Failed to load reports data. Please try again.'
            ]);
        }
    }

    public function reportDetail($id): Response
    {
        $application = Application::with([
            'user.candidatesProfile',
            'user.candidatesCV',
            'user.candidatesSocialMedia',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'status',
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->latest();
            },
            'userAnswers' => function($query) {
                $query->with(['question.choices', 'choice']);
            },
            'report'
        ])->findOrFail($id);

        // Get all histories for stage-specific data (including inactive ones with scores)
        $allHistories = $application->history;

        // Get stage-specific histories
        $administrationHistory = $allHistories->filter(function($history) {
            return $history->status->code === 'admin_selection' && !is_null($history->score);
        })->first();

        $assessmentHistory = $allHistories->filter(function($history) {
            return $history->status->code === 'psychotest' && !is_null($history->score);
        })->first();

        $interviewHistory = $allHistories->filter(function($history) {
            return $history->status->code === 'interview' && !is_null($history->score);
        })->first();

        // Calculate assessment score from user answers
        $assessmentScore = 0;
        if ($application->userAnswers->count() > 0) {
            $correctAnswers = $application->userAnswers->filter(function($answer) {
                return $answer->choice && $answer->choice?->is_correct ?? false;
            })->count();
            $totalAnswers = $application->userAnswers->count();
            $assessmentScore = round(($correctAnswers / $totalAnswers) * 100, 2);
        }

        return Inertia::render('admin/company/reports-detail', [
            'candidate' => [
                'id' => $application->id,
                'user' => [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'profile' => $application->user->candidatesProfile ? [
                        'full_name' => $application->user->candidatesProfile->full_name,
                        'phone' => $application->user->candidatesProfile->phone_number,
                        'address' => $application->user->candidatesProfile->address,
                        'birth_place' => $application->user->candidatesProfile->place_of_birth,
                        'birth_date' => $application->user->candidatesProfile->date_of_birth,
                        'gender' => $application->user->candidatesProfile->gender,
                    ] : null,
                    'cv' => $application->user->candidatesCV ? [
                        'path' => $application->user->candidatesCV->path,
                        'uploaded_at' => $application->user->candidatesCV->created_at,
                    ] : null,
                    'social_media' => $application->user->candidatesSocialMedia->map(fn($social) => [
                        'platform' => $social->platform_name,
                        'url' => $social->url,
                    ]),
                ],
                'vacancy' => [
                    'title' => $application->vacancyPeriod->vacancy->title,
                    'company' => [
                        'name' => $application->vacancyPeriod->vacancy->company->name,
                    ],
                    'period' => [
                        'name' => $application->vacancyPeriod->period->name,
                        'start_time' => $application->vacancyPeriod->period->start_time,
                        'end_time' => $application->vacancyPeriod->period->end_time,
                    ],
                ],
                'stages' => [
                    'administration' => [
                        'status' => $administrationHistory?->status->code ?? 'pending',
                        'score' => $administrationHistory?->score,
                        'notes' => $administrationHistory?->notes,
                        'processed_at' => $administrationHistory?->processed_at,
                        'reviewed_by' => $administrationHistory?->reviewer?->name,
                    ],
                    'assessment' => [
                        'status' => $assessmentHistory?->status->code ?? 'pending',
                        'score' => $assessmentHistory?->score ?: $assessmentScore,
                        'started_at' => $assessmentHistory?->processed_at,
                        'completed_at' => $assessmentHistory?->completed_at,
                        'answers' => $application->userAnswers->map(fn($answer) => [
                            'question' => [
                                'text' => $answer->question->question_text,
                                'choices' => $answer->question->choices->map(fn($choice) => [
                                    'text' => $choice->choice_text,
                                    'is_correct' => $choice->is_correct,
                                ]),
                            ],
                            'selected_answer' => [
                                'text' => $answer->choice?->choice_text ?? 'No answer selected',
                                'is_correct' => $answer->choice?->is_correct ?? false,
                            ],
                        ]),
                    ],
                    'interview' => [
                        'status' => $interviewHistory?->status->code ?? 'pending',
                        'score' => $interviewHistory?->score,
                        'notes' => $interviewHistory?->notes,
                        'scheduled_at' => $interviewHistory?->scheduled_at,
                        'completed_at' => $interviewHistory?->completed_at,
                        'interviewer' => $interviewHistory?->reviewer ? [
                            'name' => $interviewHistory->reviewer->name,
                            'email' => $interviewHistory->reviewer->email,
                        ] : null,
                    ],
                ],
                'average_score' => $application->report?->overall_score,
                'status' => [
                    'name' => $application->status->name,
                    'code' => $application->status->code,
                ],
                'final_decision' => [
                    'status' => $application->report?->final_decision ?? 'pending',
                    'notes' => $application->report?->final_notes,
                    'decided_by' => $application->report?->decisionMaker?->name ?? null,
                    'decided_at' => $application->report?->decision_made_at,
                ],
                'history' => $allHistories->map(fn($history) => [
                    'stage' => $history->status->stage,
                    'status' => [
                        'name' => $history->status->name,
                        'code' => $history->status->code,
                    ],
                    'notes' => $history->notes,
                    'score' => $history->score,
                    'processed_at' => $history->processed_at,
                    'scheduled_at' => $history->scheduled_at,
                    'completed_at' => $history->completed_at,
                    'reviewer' => $history->reviewer ? [
                        'name' => $history->reviewer->name,
                        'email' => $history->reviewer->email,
                    ] : null,
                ]),
            ],
        ]);
    }

    public function reportAction(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:passed,rejected',
            'notes' => 'nullable|string',
        ]);

        $application = Application::with(['report', 'history'])->findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // Update the application report with final decision
            $finalDecision = $validated['status'] === 'passed' ? 'accepted' : 'rejected';
            
            $application->report()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'final_decision' => $finalDecision,
                    'final_notes' => $validated['notes'],
                    'decision_made_by' => Auth::id(),
                    'decision_made_at' => now(),
                ]
            );

            // Get the appropriate final status for application table
            $statusCode = $validated['status'] === 'passed' ? 'accepted' : 'rejected';
            $finalStatus = Status::where('code', $statusCode)->first();

            if (!$finalStatus) {
                throw new \Exception("Status '{$statusCode}' not found");
            }

            // Update the application status to final status
            $application->update(['status_id' => $finalStatus->id]);

            // IMPORTANT: Do NOT add new history record
            // Just ensure the interview history (status_id = 3) remains is_active = 0
            // which should already be set when interview was completed

            DB::commit();
            
            $message = $validated['status'] === 'passed' 
                ? 'Candidate accepted successfully' 
                : 'Candidate rejected successfully';
                
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Report action error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to process final decision: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle stage action (accept/reject) from StageActionDialog
     */
    public function stageAction(Request $request, Application $application, string $stage)
    {
        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:passed,rejected',
            'score' => 'nullable|numeric|min:10|max:99',
            'notes' => 'nullable|string',
            'zoom_url' => 'nullable|url',
            'scheduled_at' => 'nullable|date',
        ]);

        // Load necessary relations
        $application->load(['vacancyPeriod.vacancy', 'vacancyPeriod.period']);

        DB::beginTransaction();
        
        try {
            // Map stage names to match database
            $stageMap = [
                'administration' => 'administrative_selection',
                'assessment' => 'psychological_test',
                'interview' => 'interview',
                'psychological_test' => 'psychological_test',
                'administrative_selection' => 'administrative_selection',
                'final' => 'final'
            ];
            
            $mappedStage = $stageMap[$stage] ?? $stage;
            
            // Load necessary relations based on stage
            if ($mappedStage === 'psychological_test') {
                $application->load(['userAnswers.choice']);
            }

            // Handle final decision (hire/reject)
            if ($mappedStage === 'final') {
                // Get the appropriate final status
                $finalStatus = Status::where('code', $validated['status'] === 'passed' ? 'hired' : 'rejected')
                    ->first();
                    
                if (!$finalStatus) {
                    throw new \Exception('Final status not found');
                }

                // Mark all previous history as inactive if needed
                $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->whereIn('code', ['hired', 'rejected']);
                    })
                    ->update(['is_active' => false]);

                // Create final decision record
                $application->history()->create([
                    'status_id' => $finalStatus->id,
                    'notes' => $validated['notes'] ?? null,
                    'processed_at' => now(),
                    'completed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'is_active' => true,
                ]);

                // Update application status
                $application->update(['status_id' => $finalStatus->id]);

                DB::commit();
                return back()->with('success', $validated['status'] === 'passed' ? 'Candidate hired successfully' : 'Candidate rejected');
            }

            // For interview stage completion, we need to get the current interview status first
            if ($mappedStage === 'interview') {
                // Get the current interview status
                $interviewStatus = Status::where('code', 'interview')
                    ->where('stage', 'interview')
                    ->first();
                    
                if (!$interviewStatus) {
                    throw new \Exception('Interview status not found');
                }

                // Update the current interview history with score and mark as completed
                $currentInterviewHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'interview');
                    })
                    ->first();

                if ($currentInterviewHistory) {
                    $currentInterviewHistory->update([
                        'score' => $validated['score'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_at' => now(),
                        'is_active' => false, // Set to inactive after completion
                    ]);
                }

                // Handle interview rejection
                if ($validated['status'] === 'rejected') {
                    // Get the rejected status
                    $rejectedStatus = Status::where('code', 'rejected')->first();
                        
                    if (!$rejectedStatus) {
                        throw new \Exception('Rejected status not found');
                    }

                    // Update application status to rejected
                    $application->update(['status_id' => $rejectedStatus->id]);

                    DB::commit();
                    return back()->with('success', 'Candidate rejected successfully');
                }

                // Handle interview passing - Check if candidate has all scores from all 3 stages
                if ($validated['status'] === 'passed') {
                    $administrationHistory = $application->history()
                        ->whereHas('status', fn($q) => $q->where('code', 'admin_selection'))
                        ->where('is_active', false)
                        ->whereNotNull('score')
                        ->first();

                    $assessmentHistory = $application->history()
                        ->whereHas('status', fn($q) => $q->where('code', 'psychotest'))
                        ->where('is_active', false)
                        ->whereNotNull('score')
                        ->first();

                    // Get the current interview history (the one we just updated)
                    $interviewHistory = $application->history()
                        ->whereHas('status', fn($q) => $q->where('code', 'interview'))
                        ->where('is_active', false)
                        ->whereNotNull('score')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // If all 3 scores exist, create application report and mark as pending
                    if ($administrationHistory?->score && $assessmentHistory?->score && $interviewHistory?->score) {
                        $overallScore = round(($administrationHistory->score + $assessmentHistory->score + $interviewHistory->score) / 3, 2);

                        // Create or update application report
                        $application->report()->updateOrCreate(
                            ['application_id' => $application->id],
                            [
                                'overall_score' => $overallScore,
                                'final_decision' => 'pending',
                                'final_notes' => null, // Will be filled when HR makes final decision
                                'decision_made_by' => null,
                                'decision_made_at' => null,
                            ]
                        );

                        // Keep the application status as interview (ID 3) for now
                        // The application will only change status when final decision is made in reports
                        $application->update(['status_id' => $interviewStatus->id]);

                        DB::commit();
                        
                        // Redirect to reports page to show this candidate is ready for final decision
                        $application->load(['vacancyPeriod.vacancy.company', 'vacancyPeriod.period']);
                        $companyId = $application->vacancyPeriod->vacancy->company_id;
                        $periodId = $application->vacancyPeriod->period_id;
                        
                        return redirect()->route('admin.recruitment.reports', [
                            'company' => $companyId,
                            'period' => $periodId
                        ])->with('success', 'Interview completed successfully. Candidate moved to reports for final decision.');
                    } else {
                        // If not all scores are available, just keep in interview stage
                        $application->update(['status_id' => $interviewStatus->id]);
                        
                        DB::commit();
                        return back()->with('success', 'Interview completed successfully. Waiting for other stage scores.');
                    }
                }
            }

            // For interview stage rejection
            if ($mappedStage === 'interview' && $validated['status'] === 'rejected') {
                // Get the rejected status
                $rejectedStatus = Status::where('code', 'rejected')->first();
                    
                if (!$rejectedStatus) {
                    throw new \Exception('Rejected status not found');
                }

                // Update the current interview history and mark as completed & rejected
                $currentInterviewHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'interview');
                    })
                    ->first();

                if ($currentInterviewHistory) {
                    $currentInterviewHistory->update([
                        'score' => $validated['score'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'is_active' => false, // Deactivate since rejected
                    ]);
                }

                // Deactivate ALL other active histories
                $application->history()
                    ->where('is_active', true)
                    ->where('id', '!=', $currentInterviewHistory?->id)
                    ->update(['is_active' => false]);

                // Update application status to rejected
                $application->update(['status_id' => $rejectedStatus->id]);

                DB::commit();
                return back()->with('success', 'Candidate rejected at interview stage');
            }

            // For interview stage passing (hire)
            if ($mappedStage === 'interview' && $validated['status'] === 'passed') {
                // Get the hired status
                $hiredStatus = Status::where('code', 'hired')->first();
                    
                if (!$hiredStatus) {
                    throw new \Exception('Hired status not found');
                }

                // Update the current interview history and mark as completed & hired
                $currentInterviewHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'interview');
                    })
                    ->first();

                if ($currentInterviewHistory) {
                    $currentInterviewHistory->update([
                        'score' => $validated['score'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'is_active' => false, // Deactivate as completed
                    ]);
                }

                // Create final hired history
                $application->history()->create([
                    'status_id' => $hiredStatus->id,
                    'notes' => 'Candidate hired after successful interview',
                    'score' => $validated['score'] ?? null,
                    'processed_at' => now(),
                    'completed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'is_active' => true,
                ]);

                // Update application status to hired
                $application->update(['status_id' => $hiredStatus->id]);

                DB::commit();
                return back()->with('success', 'Candidate hired successfully');
            }

            // For assessment stage passing to interview
            if ($mappedStage === 'psychological_test' && $validated['status'] === 'passed') {
                // Get the interview status
                $interviewStatus = Status::where('code', 'interview')
                    ->first();
                    
                if (!$interviewStatus) {
                    throw new \Exception('Interview status not found');
                }

                // Load vacancy relation if not already loaded
                $application->load('vacancyPeriod.vacancy.questionPack');
                
                // Use the new scoring logic
                $calculatedScore = $this->calculateTestScore($application);
                
                // For psychological tests, require manual score
                if ($calculatedScore === null) {
                    // This is a psychological test - use manual score if provided
                    $calculatedScore = $validated['score'] ?? null;
                    
                    if ($calculatedScore === null) {
                        throw new \Exception('Manual score is required for psychological tests');
                    }
                }

                // Update the current assessment history with score and mark as completed
                $currentAssessmentHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'psychotest');
                    })
                    ->first();

                if ($currentAssessmentHistory) {
                    $currentAssessmentHistory->update([
                        'score' => $calculatedScore, // Use calculated score from user answers
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                }

                // Deactivate ALL active histories EXCEPT the current one we just updated
                $application->history()
                    ->where('is_active', true)
                    ->where('id', '!=', $currentAssessmentHistory?->id)
                    ->update(['is_active' => false]);

                // Now deactivate the current assessment history (preserving the score)
                if ($currentAssessmentHistory) {
                    $currentAssessmentHistory->update(['is_active' => false]);
                }

                // Create new interview history
                $application->history()->create([
                    'status_id' => $interviewStatus->id,
                    'notes' => null,
                    'score' => null,
                    'processed_at' => now(),
                    'resource_url' => $validated['zoom_url'] ?? null,
                    'scheduled_at' => $validated['scheduled_at'] ?? null,
                    'reviewed_by' => Auth::id(),
                    'is_active' => true,
                ]);

                // Update application status to interview
                $application->update(['status_id' => $interviewStatus->id]);

                DB::commit();
                
                // Redirect to interview page with company and period parameters
                $companyId = $application->vacancyPeriod->vacancy->company_id;
                $periodId = $application->vacancyPeriod->period_id;
                
                return redirect()->route('admin.recruitment.interview.index', [
                    'company' => $companyId,
                    'period' => $periodId
                ])->with('success', 'Candidate moved to interview stage successfully');
            }

            // For assessment stage rejection
            if ($mappedStage === 'psychological_test' && $validated['status'] === 'rejected') {
                // Get the rejected status
                $rejectedStatus = Status::where('code', 'rejected')->first();
                    
                if (!$rejectedStatus) {
                    throw new \Exception('Rejected status not found');
                }

                // Load vacancy relation if not already loaded
                $application->load('vacancyPeriod.vacancy.questionPack');
                
                // Use the new scoring logic
                $calculatedScore = $this->calculateTestScore($application);
                
                // For psychological tests, use manual score if provided
                if ($calculatedScore === null) {
                    $calculatedScore = $validated['score'] ?? 0;
                }

                // Update the current assessment history with score and mark as completed
                $currentAssessmentHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'psychotest');
                    })
                    ->first();

                if ($currentAssessmentHistory) {
                    $currentAssessmentHistory->update([
                        'score' => $calculatedScore, // Use calculated score from user answers
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'is_active' => false, // Deactivate since rejected
                    ]);
                }

                // Deactivate ALL other active histories
                $application->history()
                    ->where('is_active', true)
                    ->where('id', '!=', $currentAssessmentHistory?->id)
                    ->update(['is_active' => false]);

                // Update application status to rejected
                $application->update(['status_id' => $rejectedStatus->id]);

                DB::commit();
                return back()->with('success', 'Candidate rejected successfully');
            }

            // For administration stage passing to assessment
            if ($mappedStage === 'administrative_selection' && $validated['status'] === 'passed') {
                // Get the assessment status
                $assessmentStatus = Status::where('code', 'psychotest')
                    ->first();
                    
                if (!$assessmentStatus) {
                    throw new \Exception('Assessment status not found');
                }

                // Update the current administration history with score and mark as completed
                $currentAdminHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'admin_selection');
                    })
                    ->first();

                if ($currentAdminHistory) {
                    $currentAdminHistory->update([
                        'score' => $validated['score'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                }

                // Deactivate ALL active histories EXCEPT the current one we just updated
                $application->history()
                    ->where('is_active', true)
                    ->where('id', '!=', $currentAdminHistory?->id)
                    ->update(['is_active' => false]);

                // Now deactivate the current administration history (preserving the score)
                if ($currentAdminHistory) {
                    $currentAdminHistory->update(['is_active' => false]);
                }

                // Create new assessment history
                $application->history()->create([
                    'status_id' => $assessmentStatus->id,
                    'notes' => null,
                    'score' => null,
                    'processed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'is_active' => true,
                ]);

                // Update application status to assessment
                $application->update(['status_id' => $assessmentStatus->id]);

                DB::commit();
                
                // Redirect to assessment page with company and period parameters
                $companyId = $application->vacancyPeriod->vacancy->company_id;
                $periodId = $application->vacancyPeriod->period_id;
                
                return redirect()->route('admin.recruitment.assessment.index', [
                    'company' => $companyId,
                    'period' => $periodId
                ])->with('success', 'Candidate moved to assessment stage successfully');
            }

            // For administration stage rejection
            if ($mappedStage === 'administrative_selection' && $validated['status'] === 'rejected') {
                // Get the rejected status
                $rejectedStatus = Status::where('code', 'rejected')->first();
                    
                if (!$rejectedStatus) {
                    throw new \Exception('Rejected status not found');
                }

                // Update the current administration history and mark as completed & rejected
                $currentAdminHistory = $application->history()
                    ->where('is_active', true)
                    ->whereHas('status', function($q) {
                        $q->where('code', 'admin_selection');
                    })
                    ->first();

                if ($currentAdminHistory) {
                    $currentAdminHistory->update([
                        'score' => $validated['score'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'completed_at' => now(),
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'is_active' => false, // Deactivate since rejected
                    ]);
                }

                // Deactivate ALL other active histories
                $application->history()
                    ->where('is_active', true)
                    ->where('id', '!=', $currentAdminHistory?->id)
                    ->update(['is_active' => false]);

                // Update application status to rejected
                $application->update(['status_id' => $rejectedStatus->id]);

                DB::commit();
                return back()->with('success', 'Candidate rejected successfully');
            }

            // If we reach here, it's an unknown stage or action
            throw new \Exception('Unknown stage or action: ' . $mappedStage . ' - ' . $validated['status']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stage action error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to process application: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a candidate for the next stage (legacy method for routes)
     */
    public function approve(Request $request, $id)
    {
        $application = Application::with(['status', 'vacancyPeriod.vacancy', 'vacancyPeriod.period'])->findOrFail($id);
        
        // Determine the current stage based on the application status
        $currentStatus = $application->status;
        
        // Map status code to stage
        $stageMap = [
            'admin_selection' => 'administrative_selection',
            'psychotest' => 'psychological_test',
            'interview' => 'interview',
            'accepted' => 'accepted',
            'rejected' => 'rejected'
        ];
        
        $stage = $stageMap[$currentStatus->code] ?? 'administrative_selection';
        
        // Create request data for stageAction
        $requestData = new Request([
            'status' => 'passed',
            'score' => $request->input('score'),
            'notes' => $request->input('notes'),
            'zoom_url' => $request->input('zoom_url'),
            'scheduled_at' => $request->input('scheduled_at'),
        ]);
        
        return $this->stageAction($requestData, $application, $stage);
    }

    /**
     * Reject a candidate (legacy method for routes)
     */
    public function reject(Request $request, $id)
    {
        $application = Application::with(['status', 'vacancyPeriod.vacancy', 'vacancyPeriod.period'])->findOrFail($id);
        
        // Determine the current stage based on the application status
        $currentStatus = $application->status;
        
        // Map status code to stage
        $stageMap = [
            'admin_selection' => 'administrative_selection',
            'psychotest' => 'psychological_test',
            'interview' => 'interview',
            'accepted' => 'accepted',
            'rejected' => 'rejected'
        ];
        
        $stage = $stageMap[$currentStatus->code] ?? 'administrative_selection';
        
        // Create request data for stageAction
        $requestData = new Request([
            'status' => 'rejected',
            'notes' => $request->input('notes'),
        ]);
        
        return $this->stageAction($requestData, $application, $stage);
    }

    /**
     * Update assessment score when candidate completes the test
     */
    public function updateAssessmentScore(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        
        // Calculate score from user answers
        $score = $application->userAnswers->count() > 0 
            ? $application->userAnswers->filter(function($answer) {
                return $answer->choice?->is_correct ?? false;
            })->count() / $application->userAnswers->count() * 100
            : 0;

        // Update the current assessment history with the calculated score
        $currentAssessmentHistory = $application->history()
            ->where('is_active', true)
            ->whereHas('status', function($q) {
                $q->where('code', 'psychotest');
            })
            ->first();

        if ($currentAssessmentHistory) {
            $currentAssessmentHistory->update([
                'score' => $score,
                'completed_at' => now(),
            ]);
        }

        return back()->with('success', 'Assessment score updated successfully');
    }

    /**
     * Display the specified assessment detail (legacy method from AssessmentController)
     */
    public function show(string $id): Response
    {
        // Fetch application and related data from database
        $application = Application::with([
            'user',
            'vacancyPeriod.vacancy',
            'vacancyPeriod.period',
            'userAnswers.question.choices',
            'history' => function($query) {
                $query->with(['status', 'reviewer'])
                    ->whereHas('status', function($q) {
                        $q->where('stage', 'psychological_test');
                    })
                    ->where('is_active', true)
                    ->latest();
            }
        ])->findOrFail($id);

        $currentHistory = $application->history->first();
        
        // Calculate assessment score
        $assessmentScore = $application->userAnswers->count() > 0 
            ? $application->userAnswers->filter(function($answer) {
                return $answer->choice?->is_correct ?? false;
            })->count() / $application->userAnswers->count() * 100
            : 0;

        $assessmentData = [
            'id' => $application->id,
            'name' => $application->user->name,
            'email' => $application->user->email,
            'phone' => $application->user->candidatesProfile?->phone ?? null,
            'position' => $application->vacancyPeriod->vacancy->title ?? '-',
            'vacancy' => $application->vacancyPeriod->vacancy->title ?? '-',
            'company_id' => $application->vacancyPeriod->vacancy->company_id ?? null,
            'period_id' => $application->vacancyPeriod->period_id ?? null,
            'registration_date' => $application->created_at->format('Y-m-d'),
            'assessment_date' => $currentHistory?->completed_at ? $currentHistory->completed_at->format('Y-m-d') : null,
            'cv_path' => $application->user->candidatesCV?->path ?? null,
            'portfolio_path' => null, // Not implemented in current system
            'cover_letter' => null, // Not implemented in current system
            'status' => $currentHistory?->status?->code ?? 'pending',
            'total_score' => $assessmentScore,
            'max_total_score' => 100,
            'notes' => $currentHistory?->notes,
            'questions' => $application->userAnswers->map(fn($answer) => [
                'id' => $answer->question_id,
                'question' => $answer->question->question_text,
                'answer' => $answer->choice?->choice_text ?? 'No answer selected',
                'score' => $answer->choice?->is_correct ?? false ? 100 : 0,
                'maxScore' => 100,
                'category' => 'psychological_test',
            ])->toArray(),
        ];

        return Inertia::render('admin/company/assessment-detail', [
            'assessment' => $assessmentData
        ]);
    }

    /**
     * Display administration detail (from AdministrationController)
     */
    public function administrationShow($id): Response
    {
        // Use the existing administrationDetail method which returns the correct data structure
        return $this->administrationDetail($id);
    }

    /**
     * Calculate duration between two timestamps
     */
    private function calculateDuration($startTime, $endTime)
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        $duration = $start->diffInMinutes($end);
        
        if ($duration < 60) {
            return $duration . ' Menit';
        } else {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return $hours . ' Jam ' . $minutes . ' Menit';
        }
    }

    /**
     * Download CV for a candidate
     */
    public function downloadCV($id)
    {
        $application = Application::with(['user.candidatesCV'])->findOrFail($id);
        
        if (!$application->user->candidatesCV) {
            abort(404, 'CV not found');
        }
        
        $cv = $application->user->candidatesCV;
        
        // Check if file exists
        $filePath = storage_path('app/' . $cv->cv_path);
        if (!file_exists($filePath)) {
            // Try alternative path
            $filePath = public_path($cv->cv_path);
            if (!file_exists($filePath)) {
                abort(404, 'CV file not found');
            }
        }
        
        // Update download count and last downloaded time
        $cv->increment('download_count');
        $cv->update(['last_downloaded_at' => now()]);
        
        return response()->download($filePath, $cv->cv_filename);
    }

    /**
     * Generate CV for a candidate (Admin function)
     */
    public function generateCV($id)
    {
        try {
            $application = Application::with(['user'])->findOrFail($id);
            $user = $application->user;
            
            // Get all user data for CV using the same method as CVGeneratorController
            $userData = $this->getUserDataForCV($user);
            
            // Generate PDF using the template
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('cv.template', $userData);
            $pdf->setPaper('A4', 'portrait');
            
            // Create filename
            $filename = 'CV_' . str_replace(' ', '_', $user->name) . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Create directory if not exists
            $directory = 'cv/' . $user->id;
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);
            
            // Save PDF file
            $filePath = $directory . '/' . $filename;
            $pdf->save(storage_path('app/public/' . $filePath));
            
            // Save CV record to database
            $cvRecord = \App\Models\CandidatesCV::create([
                'user_id' => $user->id,
                'cv_filename' => $filename,
                'cv_path' => $filePath,
                'download_count' => 0,
                'is_active' => true,
                'cv_data_snapshot' => json_encode($userData)
            ]);
            
            // Return the generated CV file for download
            return response()->download(storage_path('app/public/' . $filePath), $filename);
            
        } catch (\Exception $e) {
            abort(500, 'Gagal generate CV: ' . $e->getMessage());
        }
    }

    /**
     * Check if the application uses psychological test
     */
    private function isPsychologicalTest($application): bool
    {
        $questionPack = $application->vacancyPeriod->vacancy->questionPack ?? null;
        
        if (!$questionPack) {
            return false;
        }
        
        // Consider test types that should use manual scoring
        $psychologicalTestTypes = ['psychological', 'psychology', 'psikologi', 'general'];
        
        return in_array(strtolower($questionPack->test_type), $psychologicalTestTypes);
    }

    /**
     * Calculate score for psychological test (returns null for manual scoring)
     * Calculate score for technical test (returns calculated score)
     */
    private function calculateTestScore($application): ?float
    {
        if ($this->isPsychologicalTest($application)) {
            // For psychological tests, don't calculate automatic score
            // Return null to indicate manual scoring is required
            return null;
        }
        
        // For technical/other tests, calculate score normally
        $userAnswers = $application->userAnswers()->with('choice')->get();
        if ($userAnswers->isEmpty()) {
            return 0;
        }
        
        $correctAnswers = $userAnswers->filter(function($answer) {
            return $answer->choice && $answer->choice?->is_correct ?? false;
        })->count();
        
        $totalAnswers = $userAnswers->count();
        return round(($correctAnswers / $totalAnswers) * 100, 2);
    }

    /**
     * Get all user data for CV generation (same as CVGeneratorController)
     */
    private function getUserDataForCV($user)
    {
        return [
            'user' => $user,
            'profile' => $user->candidatesProfile,
            'educations' => $user->candidatesEducations()->with(['educationLevel', 'major'])->get(),
            'workExperiences' => $user->candidatesWorkExperiences,
            'skills' => $user->candidatesSkills,
            'achievements' => $user->candidatesAchievements,
            'organizations' => $user->candidatesOrganizations,
            'courses' => $user->candidatesCourses,
            'certifications' => $user->candidatesCertifications,
            'languages' => $user->candidatesLanguages,
            'socialMedia' => $user->candidatesSocialMedia,
        ];
    }

    /**
     * Export psychological test answers for manual correction
     */
    public function exportPsychologicalTestAnswers(Request $request, $id)
    {
        try {
            $format = $request->input('format', 'pdf');
            
            // Load application with all necessary relationships
            $application = Application::with([
                'user.candidatesProfile',
                'vacancyPeriod.vacancy.company',
                'vacancyPeriod.period',
                'userAnswers.question.choices',
                'userAnswers.choice',
                'history' => function ($query) {
                    $query->whereHas('status', function ($q) {
                        $q->where('code', 'psychotest');
                    })->with(['reviewer'])->orderBy('processed_at', 'desc');
                }
            ])->findOrFail($id);

            if ($format === 'pdf') {
                return $this->exportPsychologicalTestToPdf($application);
            } elseif ($format === 'excel') {
                return $this->exportPsychologicalTestToExcel($application);
            }

            throw new \Exception('Invalid export format');

        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage(), [
                'id' => $id,
                'format' => $request->input('format'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export psychological test answers to PDF format
     */
    private function exportPsychologicalTestToPdf($application)
    {
        Log::info('PDF export started', ['application_id' => $application->id]);

        // Get current psychological test history with reviewer information
        $currentHistory = $application->history()
            ->whereHas('status', function($q) {
                $q->where('code', 'psychotest');
            })
            ->with(['reviewer'])
            ->orderBy('processed_at', 'desc')
            ->first();

        Log::info('History loaded', [
            'has_history' => !is_null($currentHistory),
            'reviewer_name' => $currentHistory?->reviewer?->name
        ]);

        $data = [
            'application' => $application,
            'candidate' => [
                'name' => $application->user->name,
                'email' => $application->user->email,
                'phone' => $application->user->candidatesProfile?->phone_number,
                'position' => $application->vacancyPeriod->vacancy->title,
                'company' => $application->vacancyPeriod->vacancy->company->name,
                'period' => $application->vacancyPeriod->period->name,
            ],
            'answers' => $application->userAnswers->map(function($answer, $index) {
                return [
                    'number' => $index + 1,
                    'question' => $answer->question->question_text,
                    'selected_answer' => $answer->choice?->choice_text ?? 'No answer selected',
                    'all_choices' => $answer->question->choices->map(function($choice, $choiceIndex) {
                        return chr(65 + $choiceIndex) . '. ' . $choice->choice_text;
                    })->implode("\n"),
                ];
            }),
            'test_date' => $application->history->first()?->processed_at,
            'export_date' => now(),
            'psychological_history' => $currentHistory,
            'manual_score' => $currentHistory?->score,
            'reviewer_name' => $currentHistory?->reviewer?->name,
            'review_date' => $currentHistory?->reviewed_at,
            'review_notes' => $currentHistory?->notes,
            'status' => $currentHistory?->completed_at ? 'Completed' : 'In Progress',
        ];

        Log::info('Data prepared for PDF', [
            'answers_count' => $data['answers']->count(),
            'has_manual_score' => !is_null($data['manual_score']),
            'candidate_name' => $data['candidate']['name']
        ]);

        try {
            Log::info('Loading PDF view');
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.psychological-test-answers', $data);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'Psychological_Test_Answers_' . 
                       str_replace(' ', '_', $application->user->name) . '_' . 
                       date('Y-m-d_H-i-s') . '.pdf';

            Log::info('PDF generated successfully', ['filename' => $filename]);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Export psychological test answers to Excel format
     */
    private function exportPsychologicalTestToExcel($application)
    {
        // Get current psychological test history with reviewer information
        $currentHistory = $application->history()
            ->whereHas('status', function($q) {
                $q->where('code', 'psychotest');
            })
            ->with(['reviewer'])
            ->orderBy('processed_at', 'desc')
            ->first();

        $filename = 'Psychological_Test_Answers_' . 
                   str_replace(' ', '_', $application->user->name) . '_' . 
                   date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($application, $currentHistory) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header information
            fputcsv($file, ['LEMBAR JAWABAN TEST PSIKOLOGI']);
            fputcsv($file, ['']);
            fputcsv($file, ['Nama Kandidat', $application->user->name]);
            fputcsv($file, ['Email', $application->user->email]);
            fputcsv($file, ['No. Telepon', $application->user->candidatesProfile?->phone_number ?? '-']);
            fputcsv($file, ['Posisi yang Dilamar', $application->vacancyPeriod->vacancy->title]);
            fputcsv($file, ['Perusahaan', $application->vacancyPeriod->vacancy->company->name]);
            fputcsv($file, ['Periode', $application->vacancyPeriod->period->name]);
            fputcsv($file, ['Tanggal Test', $application->history->first()?->processed_at?->format('d-m-Y H:i:s') ?? '-']);
            fputcsv($file, ['Tanggal Export', now()->format('d-m-Y H:i:s')]);
            fputcsv($file, ['']);
            
            // Current scoring information if available
            if ($currentHistory) {
                fputcsv($file, ['=== INFORMASI PENILAIAN ===']);
                fputcsv($file, ['Status Penilaian', $currentHistory->completed_at ? 'Selesai' : 'Dalam Proses']);
                fputcsv($file, ['Skor Manual', $currentHistory->score ?? 'Belum dinilai']);
                fputcsv($file, ['Reviewer', $currentHistory->reviewer?->name ?? 'Belum ada reviewer']);
                fputcsv($file, ['Tanggal Review', $currentHistory->reviewed_at?->format('d-m-Y H:i:s') ?? 'Belum direview']);
                fputcsv($file, ['Catatan Reviewer', $currentHistory->notes ?? 'Tidak ada catatan']);
                fputcsv($file, ['']);
            }
            
            fputcsv($file, ['']);
            
            // Table headers
            fputcsv($file, ['No.', 'Pertanyaan', 'Jawaban Terpilih', 'Semua Pilihan Jawaban', 'Nilai Manual', 'Keterangan']);
            
            // Answers data
            foreach ($application->userAnswers as $index => $answer) {
                $allChoices = $answer->question->choices->map(function($choice, $choiceIndex) {
                    return chr(65 + $choiceIndex) . '. ' . $choice->choice_text;
                })->implode(' | ');
                
                fputcsv($file, [
                    $index + 1,
                    $answer->question->question_text,
                    $answer->choice?->choice_text ?? 'No answer selected',
                    $allChoices,
                    '', // Empty for manual scoring per question
                    '', // Empty for notes per question
                ]);
            }
            
            fputcsv($file, ['']);
            fputcsv($file, ['']);
            fputcsv($file, ['=== PENILAIAN AKHIR ===']);
            fputcsv($file, ['Total Skor Manual', $currentHistory?->score ?? '']);
            fputcsv($file, ['Status Akhir', '', '', '', '', '']); // LULUS/TIDAK LULUS
            fputcsv($file, ['Catatan Evaluator', $currentHistory?->notes ?? '']);
            fputcsv($file, ['Tanggal Evaluasi', $currentHistory?->reviewed_at?->format('d-m-Y H:i:s') ?? '']);
            fputcsv($file, ['Nama Evaluator', $currentHistory?->reviewer?->name ?? '']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update psychological test score manually by admin
     */
    public function updatePsychologicalTestScore(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:passed,rejected',
            'notes' => 'nullable|string|max:1000',
            'manual_score' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Ensure we have a valid reviewer
            $reviewerId = Auth::id();
            if (!$reviewerId) {
                throw new \Exception('Valid reviewer authentication required');
            }

            $application = Application::with(['history.status', 'history.reviewer'])->findOrFail($id);

            // Get current psychological test history
            $currentHistory = $application->history()
                ->whereHas('status', function($q) {
                    $q->where('code', 'psychotest')
                      ->where('stage', 'psychological_test');
                })
                ->where('is_active', true)
                ->first();

            if (!$currentHistory) {
                throw new \Exception('No active psychological test history found');
            }

            // Update current history with manual score and reviewer information
            $currentHistory->update([
                'score' => $validated['manual_score'],
                'notes' => $validated['notes'],
                'completed_at' => now(),
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'is_active' => false, // Deactivate current history
            ]);

            if ($validated['status'] === 'passed') {
                // Move to next stage (interview)
                $interviewStatus = Status::where('code', 'interview')->first();
                if ($interviewStatus) {
                    // Create new interview history
                    $application->history()->create([
                        'status_id' => $interviewStatus->id,
                        'processed_at' => now(),
                        'is_active' => true,
                        'notes' => 'Moved to interview stage after passing psychological test with score: ' . $validated['manual_score'],
                    ]);

                    // Update application status
                    $application->update(['status_id' => $interviewStatus->id]);
                }

                DB::commit();
                return back()->with('success', "Psychological test scored ({$validated['manual_score']}/100) and candidate moved to interview stage");

            } else {
                // Reject candidate
                $rejectedStatus = Status::where('code', 'rejected')->first();
                if ($rejectedStatus) {
                    // Create rejection history
                    $application->history()->create([
                        'status_id' => $rejectedStatus->id,
                        'processed_at' => now(),
                        'completed_at' => now(),
                        'is_active' => true,
                        'notes' => 'Rejected at psychological test stage with score: ' . $validated['manual_score'] . '. Reason: ' . ($validated['notes'] ?? 'Score below threshold'),
                        'reviewed_by' => $reviewerId,
                        'reviewed_at' => now(),
                    ]);

                    $application->update(['status_id' => $rejectedStatus->id]);
                }

                DB::commit();
                return back()->with('success', "Candidate rejected at psychological test stage with score: {$validated['manual_score']}/100");
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Update psychological test score failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update psychological test score: ' . $e->getMessage()]);
        }
    }
}