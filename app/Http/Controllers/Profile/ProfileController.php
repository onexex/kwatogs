<?php

namespace App\Http\Controllers\Profile; // Mahalaga: May /Profile ito

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Shared password-complexity policy, applied to BOTH the self-service
     * change and the forced change so the rule lives in one place.
     */
    private function passwordRules(): array
    {
        return [
            'required',
            'confirmed',           // expects new_password_confirmation
            'different:current_password',
            Password::min(8)->mixedCase()->numbers()->symbols(),
        ];
    }

    private function passwordMessages(): array
    {
        return [
            'new_password.confirmed' => 'Hindi magkatugma ang New Password at Confirm Password.',
            'new_password.different' => 'Ang bagong password ay dapat iba sa kasalukuyan.',
            'new_password.min'       => 'Dapat ay hindi bababa sa 8 characters ang password.',
            'new_password.mixed'     => 'Dapat may malaki at maliit na letra ang password.',
            'new_password.numbers'   => 'Dapat may kahit isang numero ang password.',
            'new_password.symbols'   => 'Dapat may kahit isang simbolo ang password (e.g. !@#$).',
        ];
    }

    /**
     * Ipakita ang Profile View (Optional kung may hiwalay na page)
     */
    public function index()
    {
        return view('profile.index'); 
    }

    /**
     * AJAX Method para sa Axios Request
     */
    public function updatePassword(Request $request)
    {
        // 1. Validation Rules
        $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, Auth::user()->password)) {
                        $fail('Maling password. Pakisubukang muli.');
                    }
                }
            ],
            'new_password' => $this->passwordRules(),
        ], $this->passwordMessages());

        try {
            // 2. Hanapin ang User at i-update ang Password
            $user = User::find(Auth::id());
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // 3. Success Response para sa Axios .then()
            return response()->json([
                'status' => 200,
                'message' => 'Ang iyong password ay matagumpay na nabago.'
            ]);

        } catch (\Exception $e) {
            // 4. Error Response para sa Axios .catch()
            return response()->json([
                'status' => 500,
                'message' => 'May nag-error sa system: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Standalone "you must change your password" screen, shown by the
     * ForcePasswordChange middleware to flagged users.
     */
    public function forceChangeForm()
    {
        // Already cleared? Don't trap them on this page.
        if (! Auth::user()->must_change_password) {
            return redirect('/');
        }

        return view('profile.force_change');
    }

    /**
     * Handle the forced change. Same complexity policy as updatePassword, and
     * still requires the current password (the user just logged in with it).
     * Clearing the flag is what lets the middleware step aside afterwards.
     */
    public function forceUpdatePassword(Request $request)
    {
        $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, Auth::user()->password)) {
                        $fail('Maling password. Pakisubukang muli.');
                    }
                }
            ],
            'new_password' => $this->passwordRules(),
        ], $this->passwordMessages());

        try {
            $user = User::find(Auth::id());
            $user->update([
                'password' => Hash::make($request->new_password),
                'must_change_password' => false,
            ]);

            return response()->json([
                'status'   => 200,
                'message'  => 'Ang iyong password ay matagumpay na nabago.',
                'redirect' => url('/'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'May nag-error sa system: ' . $e->getMessage()
            ], 500);
        }
    }
}