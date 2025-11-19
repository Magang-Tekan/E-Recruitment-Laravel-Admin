<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\ApplicationHistory;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class CandidateTestController extends Controller
{
    /**
     * Show the test page for a specific application
     */
    public function show($applicationId)
    {
        try {
            $user = Auth::user();
            
            // Get application with related data
            $application = Application::with([
                'vacancy.questionPack.questions.choices',
                'history' => function($query) {
                    $query->with('status')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($applicationId);
            
            // Verify the application belongs to the logged-in user
            if ($application->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this test');
            }
            
            // Check if test is already completed
            $testStatus = $this->getTestStatus($application);
            
            if ($testStatus['is_completed']) {
                return redirect()->route('candidate.application.status', $applicationId)
                    ->with('error', 'You have already completed this test. You cannot retake it.');
            }
            
            // Check if user is in the correct stage for taking test
            if (!$this->canTakeTest($application)) {
                return redirect()->route('user.info')
                    ->with('error', 'You are not eligible to take this test at this time.');
            }
            
            // Get questions from question pack (already ordered by pivot id in relationship)
            $questions = collect();
            if ($application->vacancy->questionPack) {
                $questions = $application->vacancy->questionPack->questions()->with('choices')->get();
            }
            
            // Get user's existing answers
            $userAnswers = UserAnswer::where('user_id', $user->id)
                ->where('application_id', $applicationId)
                ->pluck('choice_id', 'question_id')
                ->toArray();
            
            return Inertia::render('candidate/tests/candidate-psychotest', [
                'application' => [
                    'id' => $application->id,
                    'vacancy' => [
                        'title' => $application->vacancy->title,
                        'company' => $application->vacancy->company->name ?? 'PT AmbaTech',
                        'psychotest_name' => $application->vacancy->psychotest_name ?? 'Tes Psikologi'
                    ]
                ],
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'text' => $question->text,
                        'choices' => $question->choices->map(function ($choice) {
                            return [
                                'id' => $choice->id,
                                'text' => $choice->text,
                                'value' => $choice->value
                            ];
                        })
                    ];
                }),
                'existingAnswers' => $userAnswers,
                'timeLimit' => 3600, // 1 hour in seconds
                'testStatus' => $testStatus
            ]);
            
        } catch (\Exception $e) {
            return redirect()->route('user.info')
                ->with('error', 'Unable to load test. Please contact administrator.');
        }
    }
    
    /**
     * Save user's answer to a question
     */
    public function saveAnswer(Request $request)
    {
        try {
            $request->validate([
                'application_id' => 'required|exists:applications,id',
                'question_id' => 'required|exists:questions,id',
                'choice_id' => 'required|exists:choices,id'
            ]);
            
            $user = Auth::user();
            $applicationId = $request->application_id;
            
            // Verify application belongs to user
            $application = Application::where('id', $applicationId)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$application) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            // Check if test is already completed
            $testStatus = $this->getTestStatus($application);
            if ($testStatus['is_completed']) {
                return response()->json(['error' => 'Test already completed'], 400);
            }
            
            // Save or update the answer
            UserAnswer::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'application_id' => $applicationId,
                    'question_id' => $request->question_id
                ],
                [
                    'choice_id' => $request->choice_id
                ]
            );
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save answer'], 500);
        }
    }
    
    /**
     * Submit the test and mark it as completed
     */
    public function submitTest(Request $request, $applicationId)
    {
        try {
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Get application
            $application = Application::where('id', $applicationId)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Check if test is already completed
            $testStatus = $this->getTestStatus($application);
            if ($testStatus['is_completed']) {
                return response()->json(['error' => 'Test already completed'], 400);
            }
            
            // Calculate score
            $score = $this->calculateTestScore($user->id, $applicationId);
            
            // Get the assessment status (next stage after psychotest)
            $assessmentStatus = Status::where('code', 'assessment')->first();
            
            if (!$assessmentStatus) {
                throw new \Exception('Assessment status not found');
            }
            
            // Create history entry for test completion
            ApplicationHistory::create([
                'application_id' => $applicationId,
                'status_id' => $assessmentStatus->id,
                'processed_at' => Carbon::now(),
                'completed_at' => Carbon::now(),
                'score' => $score,
                'notes' => 'Psychotest completed automatically',
                'reviewed_by' => null
            ]);
            
            // Update application status
            $application->update(['status_id' => $assessmentStatus->id]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully',
                'score' => $score,
                'redirect_url' => route('candidate.application.status', $applicationId)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit test'], 500);
        }
    }
    
    /**
     * Show application status page
     */
    public function status($applicationId)
    {
        try {
            $user = Auth::user();
            
            $application = Application::with([
                'vacancy.company',
                'status',
                'history' => function($query) {
                    $query->with(['status', 'reviewer'])
                          ->orderBy('processed_at', 'asc'); // Show chronological order
                }
            ])->where('user_id', $user->id)
              ->findOrFail($applicationId);
            
            $testStatus = $this->getTestStatus($application);
            
            return Inertia::render('candidate/application-status', [
                'application' => [
                    'id' => $application->id,
                    'vacancy' => [
                        'title' => $application->vacancy->title,
                        'company' => $application->vacancy->company->name ?? 'Unknown Company',
                        'psychotest_name' => $application->vacancy->psychotest_name ?? 'Tes Psikologi'
                    ],
                    'status' => $application->status->name ?? 'Unknown',
                    'applied_at' => $application->created_at
                ],
                'testStatus' => $testStatus,
                'history' => $application->history->map(function ($history) {
                    return [
                        'status' => $history->status->name ?? 'Unknown',
                        'status_code' => $history->status->code ?? 'unknown',
                        'date' => $history->processed_at,
                        'completed_date' => $history->completed_at,
                        'score' => $history->score,
                        'notes' => $history->notes,
                        'reviewer' => $history->reviewer?->name,
                        'is_completed' => (bool) $history->completed_at,
                        'is_active' => (bool) $history->is_active,
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            return redirect()->route('user.info')
                ->with('error', 'Application not found or access denied.');
        }
    }
    
    /**
     * Check if user can take the test
     */
    private function canTakeTest(Application $application): bool
    {
        // Get current status
        $currentHistory = $application->history()->with('status')->latest()->first();
        
        if (!$currentHistory || !$currentHistory->status) {
            return false;
        }
        
        // User can take test if status is 'psychotest' and not completed
        return $currentHistory->status->code === 'psychotest' && !$currentHistory->completed_at;
    }
    
    /**
     * Get test completion status
     */
    private function getTestStatus(Application $application): array
    {
        $psychotestHistory = $application->history()
            ->whereHas('status', function($query) {
                $query->where('code', 'psychotest');
            })
            ->with('status')
            ->first();
        
        $isCompleted = $psychotestHistory && $psychotestHistory->completed_at;
        
        return [
            'is_completed' => $isCompleted,
            'completed_at' => $psychotestHistory?->completed_at,
            'score' => $psychotestHistory?->score,
            'can_retake' => false // Never allow retake for security
        ];
    }
    
    /**
     * Calculate test score based on user answers
     */
    private function calculateTestScore($userId, $applicationId): int
    {
        $answers = UserAnswer::where('user_id', $userId)
            ->where('application_id', $applicationId)
            ->with('choice')
            ->get();
        
        $totalScore = 0;
        foreach ($answers as $answer) {
            if ($answer->choice) {
                $totalScore += $answer->choice->value ?? 0;
            }
        }
        
        // Convert to percentage or keep as raw score
        return $totalScore;
    }
}