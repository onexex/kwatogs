<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    /**
     * Decide where a user lands at "/".
     *
     * If the user has the "home" permission, show the home dashboard.
     * Otherwise redirect them to the first menu page they are allowed to
     * access (matching the sidebar order in resources/views/layout/app.blade.php).
     * If they have no accessible page at all, send them back to login.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user && $user->can('home')) {
            return view('home');
        }

        foreach ($this->landingCandidates() as $url => $permissions) {
            foreach ($permissions as $permission) {
                if ($user && $user->can($permission)) {
                    return redirect($url);
                }
            }
        }

        // No accessible page — log out gracefully.
        return redirect('/logoutSystem')->with('fail', 'You have no pages assigned. Contact your administrator.');
    }

    /**
     * Ordered map of URL => [permissions that grant access].
     * Mirrors the sidebar so users land on the first item they can see.
     */
    private function landingCandidates(): array
    {
        return [
            '/pages/modules/registration'        => ['registration', 'enrollemployee'],

            // Operations (Workforce)
            '/pages/management/hr-dashboard'     => ['hrdashboard'],
            '/pages/modules/E201'                => ['e201'],
            '/pages/modules/earlyout'            => ['earlyout'],
            '/pages/modules/loanManagement'      => ['loanmanagement'],
            '/pages/modules/payadjustments'      => ['payadjustments'],
            '/attendance-import'                 => ['attendanceimport'],
            '/overtime-import'                   => ['overtimeimport'],
            '/leave-import'                       => ['leaveimport'],
            '/pages/modules/schedulerequests'    => ['approveschedulechange'],
            '/pages/modules/leaveApplication'    => ['leaveapplication'],
            '/pages/modules/leaverequests'       => ['pendingleaverequests'],
            '/pages/modules/obtTracker'          => ['obttracker'],
            '/pages/modules/overtime'            => ['overtime'],
            '/pages/modules/overtimerequests'    => ['pendingovertimerequests'],
            '/pages/modules/payroll'             => ['payroll'],
            '/payroll-logs'                      => ['payrolllogs'],
            '/pages/modules/adjustmentTime'      => ['manual_entry'],

            // Management (Settings)
            '/pages/management/audit-trail'      => ['auditlog'],
            '/pages/management/classification'   => ['classification'],
            '/pages/management/companies'        => ['companies'],
            '/pages/management/databasebackup'   => ['databasebackup', 'databasebackupcreate', 'databasebackuprestore', 'databasebackupdelete'],
            '/pages/management/departments'      => ['departments'],
            '/pages/management/employeestatus'   => ['employeestatus'],
            '/pages/management/holidaylogger'    => ['holidaylogger'],
            '/pages/management/leavevalidations' => ['leavevalidations'],
            '/pages/management/lilovalidations'  => ['lilovalidations'],
            '/pages/management/obvalidations'    => ['obvalidations'],
            '/pages/management/otfiling'         => ['otfiling'],
            '/pages/management/positions'        => ['positions'],
            '/employee-schedules'                => ['employeeschedules'],
            '/user-roles'                        => ['userroles'],
            '/pages/management/e201'             => ['admine201'],
            '/pages/management/leavecreditallocations' => ['leavecreditallocation'],

            // Analysis (Reports)
            '/pages/reports/attendance'          => ['attendance'],
            '/reports/employee-information'      => ['employeeinformation'],
            '/reports/overtime'                  => ['overtimereport'],
            '/reports/leave'                     => ['leavereport'],
            '/reports/thirteenth-month'          => ['thirteenthmonth'],
        ];
    }
}
