<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $authUser = Auth::user();
        
        // Debug logging to check user data (remove in production)
        // Log::info('Profile Controller - Auth User ID:', ['id' => $authUser->id]);
        // Log::info('Profile Controller - Auth User Name:', ['name' => $authUser->name]);
        // Log::info('Profile Controller - Auth User Email:', ['email' => $authUser->email]);
        // Log::info('Profile Controller - Auth User Role:', ['role' => $authUser->role->value]);

        if ($authUser->role->value == UserRole::HR->value) {
            return Inertia::render('admin/settings/profile', [
                'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
                'status' => $request->session()->get('status'),
                'user' => $authUser, // Explicitly pass user data
            ]);
        } else {
            return Inertia::render('candidate/settings/password', [
                'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
                'status' => $request->session()->get('status'),
                'user' => $authUser, // Explicitly pass user data
            ]);
        }
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Debug logging for update (remove in production)
        // Log::info('Profile Update - User ID:', ['id' => $user->id]);
        // Log::info('Profile Update - User Name:', ['name' => $user->name]);
        // Log::info('Profile Update - User Email:', ['email' => $user->email]);
        // Log::info('Profile Update - Validated Data:', $request->validated());

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        
        // Log::info('Profile Update - User saved successfully');

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
