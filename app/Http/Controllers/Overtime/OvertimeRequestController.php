<?php
namespace App\Http\Controllers\Overtime;

use App\Enums\OvertimeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Services\OvertimeService;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    public function __construct(
        private OvertimeService $service
    )
    {
    }

    public function index(Request $request)
    {
        return view('pages.modules.overtimerequest');
    }

    public function getAll()
    {
        $user = Auth::user();

        $query = Overtime::select('overtimes.*')
            ->join('emp_details', 'emp_details.id', '=', 'overtimes.emp_detail_id')
            ->join('users', 'users.empID', '=', 'emp_details.empID')
            ->where('empISID', $user->empID)
            ->where(function ($q) use ($user) {

                if ($user->can('approveovertime')) {
                    $q->orWhere('overtimes.status', OvertimeStatusEnum::FORAPPROVAL->name);
                }

                if ($user->can('approvecfoovertime')) {
                    $q->orWhere('overtimes.status', OvertimeStatusEnum::APPROVED->name);
                }

            });

        $overtimeLists = $query->paginate(10)->through(function ($overtime) {

            return [
                'employee_name' => trim(
                    $overtime->employee->user->fname . ' ' .
                    ($overtime->employee->user->fname ? $overtime->employee->user->mname . ' ' : '') .
                    $overtime->employee->user->lname
                ),
                'id' => $overtime->id,
                'fillingDate' => Carbon::parse($overtime->created_at)->format('M d, Y h:i A'),
                'date_from' => Carbon::parse($overtime->date_from . ' ' . $overtime->time_in)->format('M d, Y h:i A'),
                'date_to' => Carbon::parse($overtime->date_to . ' ' . $overtime->time_out)->format('M d, Y h:i A'),
                'days' => $overtime->total_hrs ,
                'reason' => $overtime->purpose,
                'status' => $overtime->status,
                'statusValue' => OvertimeStatusEnum::fromName($overtime->status),
            ];
        });
        return response()->json($overtimeLists);
    }

    public function updateStatus(Request $request)
    {
        $overtimeStatus = $this->service->updateStatus($request->leave_id, $request->status);

        return response()->json($overtimeStatus);
    }
}