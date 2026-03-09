<?php
namespace App\Http\Controllers\Leave;

use App\Enums\LeaveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Services\LeaveService;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestContoller extends Controller
{
    public function __construct(
        private LeaveService $service
    )
    {
    }

    public function index()
    {
        return view('pages.modules.leaveRequestList');
    }

    public function getAll(Request $request) 
    {
        $user = Auth::user();

        $query = Leave::select('leaves.*')
            ->join('emp_details', 'emp_details.empID', '=', 'leaves.employee_id')
            ->join('users', 'users.empID', '=', 'leaves.employee_id')
            ->where('empISID', $user->empID)
            ->where(function ($q) use ($user) {

                if ($user->can('approveleave')) {
                    $q->orWhere('leaves.status', LeaveStatusEnum::FORAPPROVAL->name);
                }

                if ($user->can('approvecfoleave')) {
                    $q->orWhere('leaves.status', LeaveStatusEnum::APPROVED->name);
                }

            });

        $leaveLists = $query->paginate(10)->through(function ($leave) {

            return [
                'employee_name' => trim(
                    $leave->employee->user->fname . ' ' .
                    ($leave->employee->user->fname ? $leave->employee->user->mname . ' ' : '') .
                    $leave->employee->user->lname
                ),
                'id' => $leave->id,
                'leave_type' => $leave->leaveType->type_leave,
                'fillingDate' => Carbon::parse($leave->created_at)->format('M d, Y'),
                'date_from' => Carbon::parse($leave->start_date)->format('M d, Y'),
                'date_to' => Carbon::parse($leave->end_date)->format('M d, Y'),
                'days' => $leave->total_hrs / 8,
                'reason' => $leave->reason,
                'leaveKind' => $leave->leave_kind == 0 ? 'Paid' : 'UnPaid',
                'status' => $leave->status,
                'statusValue' => LeaveStatusEnum::fromName($leave->status),
            ];
        });
        return response()->json($leaveLists);
    }

    public function updateStatus(Request $request)
    {
        $leaveStatus = $this->service->updateStatus($request->leave_id, $request->status);

        return response()->json($leaveStatus);
    }
}