<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Period;
use App\Models\Vacancies;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\Status;
use App\Models\VacancyPeriods;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyService $companyService
    ) {}

    /**
     * Show administration page for a company.
     */
    public function administration(Request $request)
    {
        $companyId = $request->query('company');
        $periodId = $request->query('period');
        
        $company = Company::findOrFail($companyId);
        
        // Get applications with administration data
        $applicationsQuery = Application::with([
            'user',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'administration',
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        });
        
        // Filter by period if specified
        if ($periodId) {
            $applicationsQuery->whereHas('vacancyPeriod', function($query) use ($periodId) {
                $query->where('period_id', $periodId);
            });
        }
        
        $applications = $applicationsQuery->get();
        
        // Format data for frontend
        $candidates = $applications->map(function ($application) {
            $vacancy = $application->vacancyPeriod->vacancy ?? null;
            $period = $application->vacancyPeriod->period ?? null;
            $administration = $application->administration;
            
            return [
                'id' => (string)$application->id,
                'name' => $application->user->name,
                'email' => $application->user->email,
                'position' => $vacancy ? $vacancy->title : 'Unknown',
                'period' => $period ? $period->name : 'Unknown',
                'registration_date' => $application->created_at->format('M d, Y'),
                'cv' => [
                    'filename' => $application->user->name . '_cv.pdf',
                    'fileType' => 'pdf',
                    'url' => '/uploads/cv/' . $application->user->name . '_cv.pdf'
                ],
                'periodId' => $period ? (string)$period->id : '1',
                'vacancy' => $vacancy ? $vacancy->title : 'Unknown',
                'admin_score' => $administration ? $administration->score : null,
                'admin_status' => $administration && isset($administration->status) ? $administration->status : 'pending',
                'admin_notes' => $administration ? $administration->notes : null,
                'documents_checked' => $administration && isset($administration->documents_checked) ? $administration->documents_checked : null,
                'requirements_met' => $administration && isset($administration->requirements_met) ? $administration->requirements_met : null,
                'reviewed_by' => $administration && $administration->reviewer ? $administration->reviewer->name : null,
                'reviewed_at' => $administration && $administration->reviewed_at ? $administration->reviewed_at->format('M d, Y H:i') : null,
            ];
        });
        
        return Inertia::render('admin/company/administration', [
            'company' => $company,
            'candidates' => $candidates,
            'users' => $candidates, // For compatibility with existing component
            'pagination' => [
                'total' => $candidates->count(),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
    }

    /**
     * Show assessment page for a company.
     */
    public function assessment(Request $request)
    {
        $companyId = $request->query('company');
        $periodId = $request->query('period');
        
        // Get applications with assessment data
        $applicationsQuery = Application::with([
            'user',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'assessment',
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        // Only get applications that have passed administration or have assessment data
        ->where(function($query) {
            $query->whereHas('administration', function($adminQuery) {
                $adminQuery->whereHas('status', function($q) {
                    $q->where('code', 'passed');
                });
            })
            ->orWhereHas('assessment');
        });
        
        // Filter by period if specified
        if ($periodId) {
            $applicationsQuery->whereHas('vacancyPeriod', function($query) use ($periodId) {
                $query->where('period_id', $periodId);
            });
        }
        
        $applications = $applicationsQuery->get();
        
        // Format data for frontend
        $assessments = $applications->map(function ($application) {
            $vacancy = $application->vacancyPeriod->vacancy ?? null;
            $period = $application->vacancyPeriod->period ?? null;
            $assessment = $application->assessment;
            
            return [
                'id' => (string)$application->id,
                'name' => $application->user->name,
                'email' => $application->user->email,
                'position' => $vacancy ? $vacancy->title : 'Unknown',
                'period' => $period ? $period->name : 'Unknown',
                'periodId' => $period ? (string)$period->id : '1',
                'vacancy' => $vacancy ? $vacancy->title : 'Unknown',
                'registration_date' => $application->created_at->format('M d, Y'),
                'test_date' => $assessment && $assessment->scheduled_at ? $assessment->scheduled_at->format('M d, Y') : null,
                'test_time' => $assessment && $assessment->scheduled_at ? $assessment->scheduled_at->format('h:i A') : null,
                'status' => $assessment ? $assessment->status : 'scheduled',
                'score' => $assessment ? $assessment->score : null,
                'test_type' => $assessment ? $assessment->test_type : null,
                'test_results' => $assessment ? $assessment->test_results : null,
                'notes' => $assessment ? $assessment->notes : null,
                'test_location' => $assessment ? $assessment->test_location : null,
                'attendance_confirmed' => $assessment ? $assessment->attendance_confirmed : false,
                'started_at' => $assessment && $assessment->started_at ? $assessment->started_at->format('M d, Y H:i') : null,
                'completed_at' => $assessment && $assessment->completed_at ? $assessment->completed_at->format('M d, Y H:i') : null,
            ];
        });
        
        return Inertia::render('admin/company/assessment', [
            'assessments' => $assessments,
            'users' => $assessments, // For compatibility
            'pagination' => [
                'total' => $assessments->count(),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
    }

    /**
     * Show interview page for a company.
     */
    public function interview(Request $request)
    {
        $companyId = $request->query('company');
        $periodId = $request->query('period');
        
        // Get applications with interview data
        $applicationsQuery = Application::with([
            'user',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'interview.reviewer',
            'interview.status',
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        // Only get applications that have passed assessment or have interview data
        ->where(function($query) {
            $query->whereHas('assessment', function($assessmentQuery) {
                $assessmentQuery->whereHas('status', function($q) {
                    $q->where('code', 'completed');
                });
            })
            ->orWhereHas('interview');
        });
        
        // Filter by period if specified
        if ($periodId) {
            $applicationsQuery->whereHas('vacancyPeriod', function($query) use ($periodId) {
                $query->where('period_id', $periodId);
            });
        }
        
        $applications = $applicationsQuery->get();
        
        // Format data for frontend
        $interviews = $applications->map(function ($application) {
            $vacancy = $application->vacancyPeriod->vacancy ?? null;
            $period = $application->vacancyPeriod->period ?? null;
            $interview = $application->interview;
            
            return [
                'id' => (string)$application->id,
                'name' => $application->user->name,
                'email' => $application->user->email,
                'position' => $vacancy ? $vacancy->title : 'Unknown',
                'period' => $period ? $period->name : 'Unknown',
                'registration_date' => $application->created_at->format('M d, Y'),
                'status' => $interview && $interview->status ? $interview->status->name : 'scheduled',
                'interview_date' => $interview && $interview->scheduled_at ? $interview->scheduled_at->format('M d, Y') : null,
                'interview_time' => $interview && $interview->scheduled_at ? $interview->scheduled_at->format('h:i A') : null,
                'interviewer' => $interview && $interview->reviewer ? $interview->reviewer->name : null,
                'interview_type' => $interview && isset($interview->interview_type) ? $interview->interview_type : 'Technical Interview',
                'score' => $interview ? $interview->score : null,
                'notes' => $interview ? $interview->notes : null,
                'feedback' => $interview ? $interview->feedback : null,
                'evaluation_criteria' => $interview ? $interview->evaluation_criteria : null,
                'is_online' => $interview ? $interview->is_online : true,
                'location' => $interview ? $interview->location : null,
                'attendance_confirmed' => $interview ? $interview->attendance_confirmed : false,
                'started_at' => $interview && $interview->started_at ? $interview->started_at->format('M d, Y H:i') : null,
                'completed_at' => $interview && $interview->completed_at ? $interview->completed_at->format('M d, Y H:i') : null,
            ];
        });
        
        return Inertia::render('admin/company/interview', [
            'interviews' => $interviews,
            'users' => $interviews, // For compatibility
            'pagination' => [
                'total' => $interviews->count(),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
    }

    /**
     * Show reports page for a company.
     */
    public function reports(Request $request)
    {
        $companyId = $request->query('companyId', 1);
        $periodId = $request->query('period');
        
        // If periodId is provided, use it to get the correct company
        if ($periodId) {
            $period = Period::with('company')->find($periodId);
            if ($period && $period->company) {
                $companyId = $period->company->id;
            }
        }
        
        // Get applications with report data
        $applicationsQuery = Application::with([
            'user',
            'vacancyPeriod.vacancy.company',
            'vacancyPeriod.period',
            'report.decisionMaker',
            'administration',
            'assessment',
            'interview',
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        // Only get applications that have reports
        ->whereHas('report');
        
        // Filter by period if specified
        if ($periodId) {
            $applicationsQuery->whereHas('vacancyPeriod', function($query) use ($periodId) {
                $query->where('period_id', $periodId);
            });
        }
        
        $applications = $applicationsQuery->get();
        
        // Format data for frontend
        $reports = $applications->map(function ($application) {
            $vacancy = $application->vacancyPeriod->vacancy ?? null;
            $period = $application->vacancyPeriod->period ?? null;
            $report = $application->report;
            $administration = $application->administration;
            $assessment = $application->assessment;
            $interview = $application->interview;
            
            return [
                'id' => (string)$application->id,
                'name' => $application->user->name,
                'email' => $application->user->email,
                'position' => $vacancy ? $vacancy->title : 'Unknown',
                'registration_date' => $application->created_at->format('M d, Y'),
                'period' => $period ? $period->name : 'Unknown',
                'overall_score' => $report ? $report->overall_score : null,
                'final_decision' => $report ? $report->final_decision : 'pending',
                'final_notes' => $report ? $report->final_notes : null,
                'rejection_reason' => $report ? $report->rejection_reason : null,
                'recommendation' => $report ? $report->recommendation : null,
                'decision_made_by' => $report && $report->decisionMaker ? $report->decisionMaker->name : null,
                'decision_made_at' => $report && $report->decision_made_at ? $report->decision_made_at->format('M d, Y H:i') : null,
                'administration_score' => $administration ? $administration->score : null,
                'assessment_score' => $assessment ? $assessment->score : null,
                'interview_score' => $interview ? $interview->score : null,
                'stage_summary' => $report ? $report->stage_summary : null,
                'strengths' => $report ? $report->strengths : null,
                'weaknesses' => $report ? $report->weaknesses : null,
                'next_steps' => $report ? $report->next_steps : null,
            ];
        });
        
        // Calculate real statistics from the data
        $totalReports = $reports->count();
        $completedReports = $reports->filter(function ($report) {
            return in_array($report['final_decision'], ['accepted', 'rejected']);
        })->count();
        $inProgressReports = $reports->filter(function ($report) {
            return $report['final_decision'] === 'pending' && $report['overall_score'] !== null;
        })->count();
        $pendingReports = $reports->filter(function ($report) {
            return $report['final_decision'] === 'pending' && $report['overall_score'] === null;
        })->count();

        return Inertia::render('admin/company/reports', [
            'reports' => $reports,
            'users' => $reports, // For compatibility
            'statistics' => [
                'total' => $totalReports,
                'completed' => $completedReports,
                'scheduled' => $inProgressReports,
                'waiting' => $pendingReports,
            ],
            'pagination' => [
                'total' => $reports->count(),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
    }

    public function index()
    {
        $companies = Company::orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        return Inertia::render('admin/companies/index', [
            'companies' => $companies
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/companies/create');
    }

    public function store(StoreCompanyRequest $request)
    {
        try {
            $company = $this->companyService->create($request);
            
            return redirect()->route('company-management.index')
                ->with('success', 'Company created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create company. Please try again.');
        }
    }

    public function show(Company $company)
    {
        return Inertia::render('admin/companies/show', [
            'company' => $company
        ]);
    }

    public function edit(Company $company)
    {
        $company->load('aboutUs');
        
        // Add logo_url to company data
        $companyData = $company->toArray();
        $companyData['logo_url'] = $company->logo_url;
        
        return Inertia::render('admin/companies/edit', [
            'company' => $companyData
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        try {
            $this->companyService->update($company, $request);
            
            return redirect()->route('company-management.index')
                ->with('success', 'Company updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update company. Please try again.');
        }
    }

    public function destroy(Company $company)
    {
        try {
            $this->companyService->delete($company);
            
            return redirect()->route('company-management.index')
                ->with('success', 'Company deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete company. Please try again.');
        }
    }

    public function periods(Company $company, Request $request)
    {
        // For company periods, we don't use pagination parameters
        // Always return all periods for the company
        
        // Get real periods associated with this company through vacancies
        $periodsQuery = Period::with([
            'vacancies.company', 
            'vacancies.questionPack',
            'vacancies.department'
        ])
        ->whereHas('vacancies', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        });
        
        $periods = $periodsQuery->get();

        // Preload application counts in a single query to avoid N+1 (possible prod timeout / 502 cause)
        $applicationCounts = \App\Models\Application::query()
            ->selectRaw('vacancy_periods.period_id as period_id, COUNT(applications.id) as total')
            ->join('vacancy_periods', 'applications.vacancy_period_id', '=', 'vacancy_periods.id')
            ->join('vacancies', 'vacancy_periods.vacancy_id', '=', 'vacancies.id')
            ->where('vacancies.company_id', $company->id)
            ->groupBy('vacancy_periods.period_id')
            ->pluck('total', 'period_id');
        
        // Get current date for status checking
        $now = \Carbon\Carbon::now();
        
        // Format the data for the frontend
    $periodsData = $periods->map(function ($period) use ($company, $now, $applicationCounts) {
            // Calculate status based on current date
            $status = 'Not Set';
            if ($period->start_time && $period->end_time) {
                $startTime = \Carbon\Carbon::parse($period->start_time);
                $endTime = \Carbon\Carbon::parse($period->end_time);
                
                if ($now->lt($startTime)) {
                    $status = 'Upcoming';
                } elseif ($now->gt($endTime)) {
                    $status = 'Closed';
                } else {
                    $status = 'Open';
                }
            }
            
            // Get vacancies for this company in this period
            $companyVacancies = $period->vacancies->where('company_id', $company->id);
            
            // Use precomputed application count (avoids per-period query)
            $applicantsCount = (int) ($applicationCounts[$period->id] ?? 0);
            
            return [
                'id' => $period->id,
                'name' => $period->name,
                'description' => $period->description,
                'start_date' => $period->start_time ? \Carbon\Carbon::parse($period->start_time)->format('d/m/Y') : null,
                'end_date' => $period->end_time ? \Carbon\Carbon::parse($period->end_time)->format('d/m/Y') : null,
                'status' => $status,
                'applicants_count' => $applicantsCount,
                'vacancies_list' => $companyVacancies->map(function ($vacancy) {
                    return [
                        'id' => $vacancy->id,
                        'title' => $vacancy->title,
                        'department' => $vacancy->department ? $vacancy->department->name : 'Unknown',
                    ];
                })->toArray(),
                'title' => $companyVacancies->first() ? $companyVacancies->first()->title : null,
                'department' => $companyVacancies->first() && $companyVacancies->first()->department ? $companyVacancies->first()->department->name : null,
                'question_pack' => $companyVacancies->first() && $companyVacancies->first()->questionPack ? $companyVacancies->first()->questionPack->pack_name : null,
                'companies' => [
                    [
                        'id' => $company->id,
                        'name' => $company->name
                    ]
                ]
            ];
        });
        
        // Get available vacancies for this company
        $vacancies = Vacancies::where('company_id', $company->id)
            ->with('department')
            ->select('id', 'title', 'department_id')
            ->get()
            ->map(function ($vacancy) {
                return [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'department' => $vacancy->department ? $vacancy->department->name : 'Unknown',
                ];
            });
        
        return Inertia::render('admin/periods/index', [
            'periods' => $periodsData->toArray(),
            'pagination' => [
                'total' => $periodsData->count(),
                'per_page' => $periodsData->count(), // Show all for company periods
                'current_page' => 1,
                'last_page' => 1,
            ],
            'company' => [
                'id' => (int) $company->id,
                'name' => $company->name,
            ],
            'vacancies' => $vacancies->toArray(),
            'filtering' => true,
        ]);
    }

    /**
     * Show interview detail page for a candidate.
     */
    public function interviewDetail($userId)
    {
        // Fetch application and related data from database
        $application = \App\Models\Application::with([
            'user',
            'vacancyPeriod.vacancy',
            'vacancyPeriod.period',
            'history.status',
        ])->findOrFail($userId);

        // Get interview history (status code: interview)
        $interview = $application->history()->whereHas('status', function($q) {
            $q->where('code', 'interview');
        })->first();

        $interviewData = [
            'id' => $application->id,
            'name' => $application->user->name,
            'email' => $application->user->email,
            'position' => $application->vacancyPeriod->vacancy->title ?? '-',
            'registration_date' => $application->created_at->format('Y-m-d'),
            'phone' => $application->user->phone ?? null,
            'cv_url' => '/uploads/cv/' . $application->user->name . '_cv.pdf',
            'interview_date' => $interview && $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d') : null,
            'interview_time' => $interview && $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : null,
            'status' => $interview && $interview->status ? $interview->status->code : 'pending',
            'notes' => $interview ? $interview->notes : null,
            'skills' => $application->user->skills ? $application->user->skills->pluck('name')->toArray() : [],
            'experience_years' => $application->user->experience_years ?? 0,
            'education' => $application->user->education ?? null,
            'portfolio_url' => $application->user->portfolio_url ?? null,
            'score' => $interview ? $interview->score : null,
            'company_id' => $application->vacancyPeriod->vacancy->company_id ?? null,
            'period_id' => $application->vacancyPeriod->period_id ?? null,
        ];

        return Inertia::render('admin/company/interview-detail', [
            'userId' => $userId,
            'interviewData' => $interviewData,
        ]);
    }

    public function approveInterview(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        
        // Update interview history
        $application->history()->updateOrCreate(
            ['status_id' => Status::where('code', 'interview')->first()->id],
            ['status_id' => Status::where('code', 'passed')->first()->id]
        );

        // Create or update application report
        ApplicationReport::updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_decision' => 'accepted',
                'final_notes' => $request->input('notes', 'Candidate accepted.'),
                'overall_score' => $request->input('score', null),
                'decision_made_by' => Auth::id(),
                'decision_made_at' => now(),
            ]
        );

        // Update application status to accepted
        $application->status_id = Status::where('code', 'accepted')->first()->id;
        $application->save();

        $companyId = $application->vacancyPeriod->vacancy->company_id;
        $periodId = $application->vacancyPeriod->period_id;

        return redirect()->route('company.reports', ['company' => $companyId, 'period' => $periodId])
            ->with('success', 'Candidate has been accepted.');
    }

    public function rejectInterview(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        // Update interview history
        $application->history()->updateOrCreate(
            ['status_id' => Status::where('code', 'interview')->first()->id],
            ['status_id' => Status::where('code', 'failed')->first()->id]
        );

        // Create or update application report
        ApplicationReport::updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_decision' => 'rejected',
                'final_notes' => $request->input('notes', 'Candidate rejected after interview.'),
                'decision_made_by' => Auth::id(),
                'decision_made_at' => now(),
            ]
        );

        // Update application status to rejected
        $application->status_id = Status::where('code', 'rejected')->first()->id;
        $application->save();

        $companyId = $application->vacancyPeriod->vacancy->company_id;
        $periodId = $application->vacancyPeriod->period_id;

        return redirect()->route('company.interview', ['company' => $companyId, 'period' => $periodId])
            ->with('success', 'Candidate has been rejected.');
    }

    /**
     * Show candidates for a company with period filter
     */
    public function candidates(Company $company, Request $request)
    {
        try {
            $periodId = $request->query('period');
            $search = $request->query('search', '');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

        // Get open periods for this company
        $now = Carbon::now();
        $openPeriods = Period::whereHas('vacancies', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->get()
        ->filter(function ($period) use ($now) {
            // Only include periods that are currently open
            if (!$period->start_time || !$period->end_time) {
                return false; // Exclude periods without dates
            }
            
            $startTime = Carbon::parse($period->start_time);
            $endTime = Carbon::parse($period->end_time);
            
            // Period is open if current time is between start and end
            return $now->gte($startTime) && $now->lte($endTime);
        })
        ->map(function ($period) {
            return [
                'id' => $period->id,
                'name' => $period->name,
                'status' => 'Open',
            ];
        })
        ->values();

        // Build query for applications
        $query = Application::with([
            'user.candidatesProfile',
            'vacancyPeriod.vacancy',
            'vacancyPeriod.period',
            'history' => function ($q) {
                $q->with('status')->latest();
            },
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        });

        // Filter by period if provided
        if ($periodId) {
            $query->whereHas('vacancyPeriod', function ($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('vacancyPeriod.vacancy', function ($vacancyQuery) use ($search) {
                    $vacancyQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Paginate
        $applications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format data for frontend
        $candidates = $applications->getCollection()->map(function ($application, $index) use ($applications) {
            $user = $application->user;
            if (!$user) {
                return null;
            }
            
            $profile = $user->candidatesProfile ?? null;
            $vacancy = $application->vacancyPeriod->vacancy ?? null;
            $period = $application->vacancyPeriod->period ?? null;
            
            // Get last stage from history
            $lastHistory = $application->history->first();
            $lastStageName = '-';
            if ($lastHistory && $lastHistory->status) {
                $lastStageName = $lastHistory->status->name ?? '-';
            }
            
            // Determine status
            $statusCode = $application->status ? $application->status->code : 'pending';
            $statusName = 'Proses Recruitment';
            if ($statusCode === 'accepted') {
                $statusName = 'Accepted';
            } elseif ($statusCode === 'rejected') {
                $statusName = 'Rejected';
            } elseif (in_array($statusCode, ['admin_selection', 'psychotest', 'interview'])) {
                $statusName = 'Proses Recruitment';
            }

            return [
                'no' => ($applications->currentPage() - 1) * $applications->perPage() + $index + 1,
                'id' => $application->id,
                'name' => $user->name ?? '-',
                'period' => $period ? $period->name : '-',
                'date_of_birth' => $profile && $profile->date_of_birth 
                    ? Carbon::parse($profile->date_of_birth)->format('d/m/Y') 
                    : '-',
                'address' => $profile && $profile->address ? $profile->address : '-',
                'position' => $vacancy ? $vacancy->title : '-',
                'last_stage' => $lastStageName,
                'status' => $statusName,
            ];
        })->filter(); // Remove null entries

        return Inertia::render('admin/company/candidates', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'periods' => $openPeriods->toArray(),
            'selectedPeriod' => $periodId ? (int)$periodId : null,
            'candidates' => $candidates->values()->toArray(),
            'pagination' => [
                'total' => $applications->total(),
                'per_page' => $applications->perPage(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'period' => $periodId ? (int)$periodId : null,
            ],
        ]);
        } catch (\Exception $e) {
            Log::error('Error in CompanyController@candidates: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'exception' => $e
            ]);
            
            return Inertia::render('admin/company/candidates', [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'periods' => [],
                'selectedPeriod' => null,
                'candidates' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'filters' => [
                    'search' => '',
                    'period' => null,
                ],
                'error' => 'Terjadi kesalahan saat memuat data. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Export candidates to Excel/CSV
     */
    public function exportCandidates(Company $company, Request $request)
    {
        $periodId = $request->query('period');
        $format = $request->query('format', 'csv');

        // Build query for applications
        $query = Application::with([
            'user.candidatesProfile',
            'vacancyPeriod.vacancy',
            'vacancyPeriod.period',
            'history' => function ($q) {
                $q->with('status')->latest();
            },
            'status'
        ])
        ->whereHas('vacancyPeriod.vacancy', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        });

        // Filter by period if provided
        if ($periodId) {
            $query->whereHas('vacancyPeriod', function ($q) use ($periodId) {
                $q->where('period_id', $periodId);
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->get();

        $filename = 'Kandidat_' . str_replace(' ', '_', $company->name) . '_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($applications) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'No',
                'Nama',
                'Periode',
                'Tanggal Lahir',
                'Alamat',
                'Posisi yang Dilamar',
                'Tahap Terakhir',
                'Status'
            ]);
            
            // Data
            $no = 1;
            foreach ($applications as $application) {
                $user = $application->user;
                $profile = $user->candidatesProfile;
                $vacancy = $application->vacancyPeriod->vacancy ?? null;
                $period = $application->vacancyPeriod->period ?? null;
                
                // Get last stage from history
                $lastHistory = $application->history->first();
                $lastStageName = $lastHistory ? $lastHistory->status->name : '-';
                
                // Determine status
                $statusCode = $application->status ? $application->status->code : 'pending';
                $statusName = 'Proses Recruitment';
                if ($statusCode === 'accepted') {
                    $statusName = 'Accepted';
                } elseif ($statusCode === 'rejected') {
                    $statusName = 'Rejected';
                } elseif (in_array($statusCode, ['admin_selection', 'psychotest', 'interview'])) {
                    $statusName = 'Proses Recruitment';
                }
                
                fputcsv($file, [
                    $no++,
                    $user->name,
                    $period ? $period->name : '-',
                    $profile && $profile->date_of_birth 
                        ? Carbon::parse($profile->date_of_birth)->format('d/m/Y') 
                        : '-',
                    $profile ? $profile->address : '-',
                    $vacancy ? $vacancy->title : '-',
                    $lastStageName,
                    $statusName,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
