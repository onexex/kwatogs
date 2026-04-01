<?php

namespace App\Http\Controllers\Profile; // Mahalaga: May /Profile ito

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
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
            'new_password' => [
                'required', 
                'min:8', 
                'confirmed', // Hahanapin nito ang new_password_confirmation
                'different:current_password'
            ],
        ], [
            // Custom Messages
            'new_password.confirmed' => 'Hindi magkatugma ang New Password at Confirm Password.',
            'new_password.different' => 'Ang bagong password ay dapat iba sa kasalukuyan.',
            'new_password.min' => 'Dapat ay hindi bababa sa 8 characters ang password.',
        ]);

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
}