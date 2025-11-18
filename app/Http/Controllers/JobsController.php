<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Vacancies;
use App\Models\VacancyPeriods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JobsController extends Controller
{
    public function index()
    {
        $vacancies = Vacancies::with(['company', 'department'])->get()->map(function ($vacancy) {
            return [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'department' => $vacancy->department ? $vacancy->department->name : 'Unknown',
                'location' => $vacancy->location,
                'salary' => $vacancy->salary,
                'company' => $vacancy->company ? $vacancy->company->name : 'Unknown',
                'requirements' => is_array($vacancy->requirements) ? $vacancy->requirements : [],
                'benefits' => is_array($vacancy->benefits) ? $vacancy->benefits : [],
                'job_description' => $vacancy->job_description,
                'created_at' => $vacancy->created_at,
                'updated_at' => $vacancy->updated_at
            ];
        });
        
        $appliedVacancyIds = [];
        if (Auth::check()) {
            // Get applied vacancy IDs through the Application -> VacancyPeriod -> Vacancy relationship
            $appliedVacancyIds = Application::where('user_id', Auth::id())
                ->with('vacancyPeriod')
                ->get()
                ->pluck('vacancyPeriod.vacancy_id')
                ->filter()
                ->toArray();
        }

        return Inertia::render('candidate/jobs/jobs-lists', [
            'vacancies' => $vacancies,
            'user' => Auth::user(),
            'appliedVacancyIds' => $appliedVacancyIds,
        ]);
    }

    public function apply(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find the vacancy
            $vacancy = Vacancies::findOrFail($id);
            
            // Find the vacancy period for this vacancy (assuming there's an active period)
            $vacancyPeriod = VacancyPeriods::where('vacancy_id', $id)
                ->whereHas('period', function($query) {
                    $query->where('status', 'active'); // Assuming there's a status field
                })
                ->first();

            if (!$vacancyPeriod) {
                return redirect()->back()->with('error', 'No active recruitment period found for this position');
            }

            // Check if user has already applied to this vacancy
            $existingApplication = Application::where('user_id', Auth::id())
                ->where('vacancy_period_id', $vacancyPeriod->id)
                ->first();

            if ($existingApplication) {
                return redirect()->back()->with('error', 'You have already applied for this position');
            }

            $user_id = Auth::id();
            Application::create([
                'user_id' => $user_id,
                'vacancy_period_id' => $vacancyPeriod->id,
                'status_id' => 1, // Assuming status_id 1 is for administrative selection
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Your application has been submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred while submitting your application. Please try again.');
        }
    }

    public function show()
    {
        return Inertia::render('candidate/chats/candidate-chat');
    }
}
