<?php namespace App\Http\Controllers\KuBo; use App\Http\Controllers\Controller; use App\Models\KuBoMessage; use App\Models\User; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\DB;

class ChatController extends Controller {
    /** Get conversation list with unread counts. */
    public function conversations(): JsonResponse {
        $uid = Auth::user()->empID;
        $convos = KuBoMessage::select(
                DB::raw("CASE WHEN sender_id = '{$uid}' THEN receiver_id ELSE sender_id END as user_id"),
                DB::raw("COUNT(CASE WHEN receiver_id = '{$uid}' AND is_read = 0 THEN 1 END) as unread"),
                DB::raw("MAX(created_at) as last_at")
            )
            ->where('sender_id', $uid)->orWhere('receiver_id', $uid)
            ->groupBy(DB::raw("CASE WHEN sender_id = '{$uid}' THEN receiver_id ELSE sender_id END"))
            ->orderByDesc('last_at')->limit(20)
            ->get();
        $users = User::whereIn('empID', $convos->pluck('user_id'))->get()->keyBy('empID');
        $list = $convos->map(function ($c) use ($users) {
            $u = $users[$c->user_id] ?? null;
            return [
                'empID' => $c->user_id,
                'name' => $u ? $u->community_full_name : 'Unknown',
                'avatar' => $u ? $u->community_avatar : '',
                'unread' => (int)$c->unread,
            ];
        });
        return response()->json(['conversations' => $list]);
    }

    /** Get messages with a specific user. */
    public function messages($empID): JsonResponse {
        $uid = Auth::user()->empID;
        $msgs = KuBoMessage::where(function ($q) use ($uid, $empID) {
                $q->where('sender_id', $uid)->where('receiver_id', $empID);
            })->orWhere(function ($q) use ($uid, $empID) {
                $q->where('sender_id', $empID)->where('receiver_id', $uid);
            })
            ->orderBy('created_at')->limit(100)->get();
        // Mark received messages as read
        KuBoMessage::where('sender_id', $empID)->where('receiver_id', $uid)->where('is_read', false)
            ->update(['is_read' => true]);
        $user = User::where('empID', $empID)->first();
        return response()->json([
            'user' => $user ? [
                'empID' => $user->empID,
                'name' => $user->community_full_name,
                'avatar' => $user->community_avatar,
            ] : null,
            'messages' => $msgs->map(fn($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'message' => $m->message,
                'is_read' => (bool)$m->is_read,
                'created_at' => $m->created_at->diffForHumans(),
            ]),
        ]);
    }

    /** Send a message. */
    public function send(Request $request, $empID): JsonResponse {
        $request->validate(['message' => 'required|string|max:2000']);
        $uid = Auth::user()->empID;
        $msg = KuBoMessage::create([
            'sender_id' => $uid,
            'receiver_id' => $empID,
            'message' => $request->message,
        ]);
        return response()->json([
            'id' => $msg->id,
            'sender_id' => $msg->sender_id,
            'message' => $msg->message,
            'created_at' => $msg->created_at->diffForHumans(),
        ]);
    }
}