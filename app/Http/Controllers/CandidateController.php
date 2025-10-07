<?php

namespace App\Http\Controllers;

use App\Models\CandidatesEducation;
use App\Models\EducationLevel;
use App\Models\MasterMajor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('candidate/candidate-dashboard', [
            'users' => $user,
        ]);
    }

    public function store()
    {
        return Inertia::render('candidate/profile/candidate-profile');
    }

    public function show()
    {
        // $candidates = Candidate::all();
        return Inertia::render('admin/candidates/candidate-list');
    }

    /**
     * Get all education records for the authenticated user
     */
    public function getEducation()
    {
        $user = Auth::user();
        $educations = CandidatesEducation::where('user_id', $user->id)
            ->with(['educationLevel', 'major'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $educations
        ]);
    }

    /**
     * Store a new education record
     */
    public function storeEducation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'education_level_id' => 'required|exists:education_levels,id',
            'faculty' => 'required|string|max:255',
            'major_id' => 'required|exists:master_majors,id',
            'institution_name' => 'required|string|max:255',
            'gpa' => 'required|numeric|min:0|max:4',
            'year_in' => 'required|integer|min:1900|max:' . date('Y'),
            'year_out' => 'nullable|integer|min:1900|max:' . (date('Y') + 10)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $education = CandidatesEducation::create([
                'user_id' => $user->id,
                'education_level_id' => $request->education_level_id,
                'faculty' => $request->faculty,
                'major_id' => $request->major_id,
                'institution_name' => $request->institution_name,
                'gpa' => $request->gpa,
                'year_in' => $request->year_in,
                'year_out' => $request->year_out,
            ]);

            $education->load(['educationLevel', 'major']);

            return response()->json([
                'success' => true,
                'message' => 'Education record created successfully',
                'data' => $education
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create education record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an education record
     */
    public function updateEducation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'education_level_id' => 'required|exists:education_levels,id',
            'faculty' => 'required|string|max:255',
            'major_id' => 'required|exists:master_majors,id',
            'institution_name' => 'required|string|max:255',
            'gpa' => 'required|numeric|min:0|max:4',
            'year_in' => 'required|integer|min:1900|max:' . date('Y'),
            'year_out' => 'nullable|integer|min:1900|max:' . (date('Y') + 10)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $education = CandidatesEducation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $education->update([
                'education_level_id' => $request->education_level_id,
                'faculty' => $request->faculty,
                'major_id' => $request->major_id,
                'institution_name' => $request->institution_name,
                'gpa' => $request->gpa,
                'year_in' => $request->year_in,
                'year_out' => $request->year_out,
            ]);

            $education->load(['educationLevel', 'major']);

            return response()->json([
                'success' => true,
                'message' => 'Education record updated successfully',
                'data' => $education
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update education record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an education record
     */
    public function deleteEducation($id)
    {
        try {
            $user = Auth::user();
            $education = CandidatesEducation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $education->delete();

            return response()->json([
                'success' => true,
                'message' => 'Education record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete education record: ' . $e->getMessage()
            ], 500);
        }
    }
}
