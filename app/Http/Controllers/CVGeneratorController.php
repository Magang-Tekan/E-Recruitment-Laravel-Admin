<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CandidatesCV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CVGeneratorController extends Controller
{
    /**
     * Display CV generator page
     */
    public function index()
    {
        $user = Auth::user();
        
        $cvs = CandidatesCV::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'cv_filename', 'download_count', 'last_downloaded_at', 'created_at']);
            
        return Inertia::render('candidate/cv/cv-generator', [
            'cvs' => $cvs
        ]);
    }

    /**
     * Generate CV for the authenticated user
     */
    public function generateCV(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get all user data for CV
            $userData = $this->getUserDataForCV($user);
            
            // Generate PDF using the template
            $pdf = Pdf::loadView('cv.template', $userData);
            $pdf->setPaper('A4', 'portrait');
            
            // Create filename
            $filename = 'CV_' . str_replace(' ', '_', $user->name) . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Create directory if not exists
            $directory = 'cv/' . $user->id;
            Storage::disk('public')->makeDirectory($directory);
            
            // Save PDF file
            $filePath = $directory . '/' . $filename;
            $pdf->save(storage_path('app/public/' . $filePath));
            
            // Save CV record to database
            $cvRecord = CandidatesCV::create([
                'user_id' => $user->id,
                'cv_filename' => $filename,
                'cv_path' => $filePath,
                'download_count' => 0,
                'is_active' => true,
                'cv_data_snapshot' => json_encode($userData)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'CV berhasil digenerate!',
                'data' => [
                    'id' => $cvRecord->id,
                    'filename' => $filename,
                    'download_url' => route('user.cv.download', $cvRecord->id),
                    'created_at' => $cvRecord->created_at->format('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate CV: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Download CV
     */
    public function downloadCV($id = null)
    {
        try {
            $user = Auth::user();
            
            if ($id) {
                // Download specific CV
                $cv = CandidatesCV::where('id', $id)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->firstOrFail();
            } else {
                // Download latest CV
                $cv = CandidatesCV::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->latest()
                    ->first();
                    
                if (!$cv) {
                    abort(404, 'CV not found');
                }
            }
            
            $filePath = storage_path('app/public/' . $cv->cv_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'CV file not found');
            }
            
            // Update download count
            $cv->increment('download_count');
            $cv->update(['last_downloaded_at' => now()]);
            
            return response()->download($filePath, $cv->cv_filename);
            
        } catch (\Exception $e) {
            abort(404, 'CV not found');
        }
    }
    
    /**
     * List all CVs for the user
     */
    public function listCVs()
    {
        $user = Auth::user();
        
        $cvs = CandidatesCV::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'cv_filename', 'download_count', 'last_downloaded_at', 'created_at']);
            
        return response()->json([
            'success' => true,
            'data' => $cvs
        ]);
    }
    
    /**
     * Delete CV
     */
    public function deleteCV($id)
    {
        try {
            $user = Auth::user();
            
            $cv = CandidatesCV::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
                
            // Delete file
            $filePath = storage_path('app/public/' . $cv->cv_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete record
            $cv->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'CV berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus CV: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all user data for CV generation
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
}
