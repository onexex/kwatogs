<?php namespace App\Http\Controllers\KuBo; use App\Http\Controllers\Controller; use App\Models\User; use Illuminate\Support\Facades\Auth;

class KuBoController extends Controller {
    public function feed() { return view('kubo.feed.index'); }
    public function explore() { return view('kubo.explore.index'); }
    public function notifications() { return view('kubo.notifications.index'); }
    public function profile($empID = null) { $user = Auth::user(); $profileUser = $empID ? User::with('empDetail.department', 'empDetail.position')->where('empID', $empID)->firstOrFail() : $user->load('empDetail.department', 'empDetail.position'); return view('kubo.profile.index', compact('profileUser')); }
}