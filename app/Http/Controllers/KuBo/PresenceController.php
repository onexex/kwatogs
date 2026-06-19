<?php namespace App\Http\Controllers\KuBo; use App\Http\Controllers\Controller; use App\Models\KuBoMessage; use App\Models\KuBoPresence; use App\Models\User; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller {
    /** Heartbeat — mark current user as online. */
    public function ping(): JsonResponse {
        $uid = Auth::user()->empID;
        KuBoPresence::updateOrCreate(
            ['user_id' => $uid],
            ['last_seen_at' => now()]
        );
        return response()->json(['status' => 'ok']);
    }

    /** Get users seen in the last 5 minutes. */
    public function online(): JsonResponse {
        $cutoff = now()->subMinutes(5);
        $uid = Auth::user()->empID;
        $users = KuBoPresence::with('user.empDetail.department')
            ->where('last_seen_at', '>=', $cutoff)
            ->where('user_id', '!=', $uid)
            ->get()
            ->map(function (KuBoPresence $p) use ($uid) {
                $u = $p->user;
                $unread = KuBoMessage::where('sender_id', $u->empID)
                    ->where('receiver_id', $uid)
                    ->where('is_read', false)
                    ->count();
                return [
                    'empID'          => $u->empID ?? '',
                    'name'           => $u->community_full_name ?? 'Unknown',
                    'avatar'         => $u->community_avatar,
                    'department'     => $u->empDetail?->department?->depName ?? '',
                    'last_seen_at'   => $p->last_seen_at ? $p->last_seen_at->diffForHumans() : '',
                    'unread'         => $unread,
                ];
            })
            ->values();
        return response()->json(['online' => $users]);
    }
}