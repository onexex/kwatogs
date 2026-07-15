<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\company;
use App\Models\PayrollPeriod;
use App\Models\agencies;
use App\Models\HMOModel;
use App\Models\joblevel;
use App\Models\position;
use App\Models\leavetype;
use App\Models\department;
use Illuminate\Http\Request;
use App\Models\classification;
use DB;
use Illuminate\Support\Facades\Auth;

class pageCtrl extends Controller
{
    public function indexUsers()
    {
        return view('pages.users.manage');
    }

      public function test()
    {
        return view('login.testmoto');
    }
    public function alas()
    {
        return view('pages.modules.alas');
    }

    public function officetime()
    {
        return view('pages.management.time');
    }

    public function companies()
    {
        return view('pages.management.companies');
    }

    public function documentation()
    {
        return view('pages.management.documentation');
    }

    // "What's New" changelog — reads public/changelog.json (generated during the
    // staging CI deploy from git history). Null when the file isn't present
    // (e.g. production, which doesn't generate it) so the view shows an empty state.
    public function whatsnew()
    {
        $path = public_path('changelog.json');
        $data = file_exists($path)
            ? json_decode(file_get_contents($path), true)
            : null;

        return view('pages.management.whatsnew', ['changelog' => $data]);
    }

    public function classification()
    {
        return view('pages.management.classification');
    }

    public function e201()
    {
        // Eager-load the relations the sidebar list reads (dept/position for the
        // label, empDetail + employeeInformation for the HR-attention deep-link
        // filters — missing gov docs / passport / regularization / birthday).
        $resultUser = User::with(['empDetail.department', 'empDetail.position', 'employeeInformation'])
        ->orderBy('lname')->orderBy('fname')
        ->where('status','1')
        ->get();

        return view('pages.management.e201')->with('resultUser',$resultUser);
    }
    public function registration(Request $request)
    {
        // When arriving from the Applicant module's "Hire" action, pre-fill the
        // onboarding form with the applicant's captured initial data. The real
        // employee is created here; registerCtrl::create then flags the applicant
        // as hired (via the hidden applicant_id field).
        $prefill = null;
        if ($request->filled('applicant')) {
            $applicant = \App\Models\Applicant::find($request->query('applicant'));
            if ($applicant && $applicant->status !== 'hired') {
                $prefill = $applicant;
            }
        }

        $getCompany=company::get();
        $getClassification=classification::get();
        // $getClassification=classification::get();
        $getDepartment=department::get();
        $getPosition=position::get();
        $getImmediateList=User::orderBy('lname')->orderBy('fname')->get();
        $getJoblevel=joblevel::get();
        $getAgency=agencies::get();
        $getHMO=HMOModel::get();

        return view('pages.modules.registration')
        ->with('prefill',$prefill)
        ->with('hmoData',$getHMO)
        ->with('agencyData',$getAgency)
        ->with('joblevelData',$getJoblevel)
        ->with('companyData',$getCompany)
        ->with('immediateData',$getImmediateList)
        ->with('positionData',$getPosition)
        ->with('departmentData',$getDepartment)
        ->with('employeeClassification',$getClassification);
    }
    public function payroll(Request $request)
    {
        $companies = Company::get();

        // Fetch all records from the classifications table
        $classifications = DB::table('classifications')->get();

        // Fetch all departments for the department filter
        $departments = department::get();

        // Get the current classification filter from the URL parameter (defaults to 'all')
        $selectedClassification = $request->query('classification', 'all');

        // Payroll schedule (pay date + cut-off) per company, keyed by comp_id for the UI selector.
        $periodsByCompanyId = PayrollPeriod::orderBy('sort')->orderBy('id')->get()->groupBy('company_id');
        $companyPeriods = [];
        foreach ($companies as $c) {
            $companyPeriods[$c->comp_id] = $periodsByCompanyId->get($c->id, collect())->values();
        }

        return view('pages.modules.payroll', compact('companies', 'classifications', 'departments', 'selectedClassification', 'companyPeriods'));
    }

    // JMC
    public function agencies()
    {
        return view('pages.management.agencies');
    }

    public function positions()
    {
        $getJobeLevel=joblevel::orderBy('job_desc','asc')->get();
        return view('pages.management.positions')->with('joblevelData',$getJobeLevel);
    }

    public function joblevels()
    {

        return view('pages.management.jobLevels');
    }

    public function hmo()
    {
        return view('pages.management.hmo');
    }

    public function employeestatus()
    {
        return view('pages.management.employeeStatus');
    }

    public function leavetypes()
    {
        return view('pages.management.leaveTypes');
    }

    public function userroles()
    {
        return view('pages.management.userRole');
    }

    public function otfiling()
    {
        $getCompany=company::get();
        return view('pages.management.OTFile')->with('companyData',$getCompany);
    }

    public function eo()
    {
        return view('pages.management.eo');
    }

    public function philhealth()
    {
        return view('pages.management.philhealth');
    }

    public function sil()
    {
        return view('pages.management.sil');
    }

    public function parental()
    {
        $resultEmp = User::orderBy('lname')->orderBy('fname')
        ->get();
        return view('pages.management.parentalSetting')->with('resultEmp',$resultEmp);
    }

    public function shifts()
    {
        return view('pages.management.shifts');
    }

    public function archive()
    {
        $result = DB::table('positions')
        ->orderBy('pos_desc')
        ->get();
        return view('pages.management.archive')->with('result',$result);

        // return view('pages.management.archive');
    }

    public function E201Mgt()
    {
        return view('pages.management.E201');
    }

    // REPORTS

    public function alasView()
    {
        return view('pages.reports.alas');
    }

    public function attendanceView()
    {

        $resultEmp = User::orderBy('lname')->orderBy('fname')
        ->where('status','1')
        ->get();

        $departments = \App\Models\department::orderBy('dep_name')->get();

        return view('pages.reports.attendance')->with('resultEmp',$resultEmp)->with('departments',$departments);
    }

    public function darView()
    {
        return view('pages.reports.dar');
    }

    public function eoView()
    {
        return view('pages.reports.eo');
    }

    public function obView()
    {
        return view('pages.reports.ob');
    }

    public function otView()
    {
        return view('pages.reports.ot');
    }

    public function leaveView()
    {
        return view('pages.reports.leaveCredit');
    }

    public function e201File()
    {
        $user = Auth::user();
        $user->load('employmentDocuments');   // for the read-only "My Documents" section (can:viewe201files)
        $empployeeDetails = $user->empDetail;
        return view('pages.modules.201', [
            'emp' => $empployeeDetails,
            'user' => $user,
        ]);
    }
    public function memorandum()
    {
        return view('pages.modules.memorandum');
    }

    //SHAIRA//
    //MANAGEMENT

    public function accessrights()
    {
        return view('pages.management.accessrights');
    }

    public function departments()
    {
        return view('pages.management.departments');
    }

    public function relationship()
    {
        return view('pages.management.relationship');
    }

    public function leavevalidations()
    {
        $getLeavetype=leavetype::get();
        $getCompany=company::get();
        return view('pages.management.leavevalidations')->with('leaveTypeData',$getLeavetype)->with('company',$getCompany);
    }

    public function holidaylogger()
    {
        $departments = department::orderBy('dep_name')->get();
        return view('pages.management.holidaylogger')->with('departments', $departments);
    }

    public function lilovalidations()
    {
    }

    public function obvalidations()
    {
        return view('pages.management.obvalidations');
    }

    public function ssscontribution()
    {
        return view('pages.management.ssscontribution');
    }

    public function pagibigcontribution()
    {
        return view('pages.management.pagibigcontribution');
    }

    //MODULE
    public function obtTracker()
    {
        return view('pages.modules.obtTracker');
    }

    public function sendOBT()
    {
        return view('pages.modules.sendOBT');
    }

    public function overtime()
    {
    }

    public function earlyout()
    {
        return view('pages.modules.earlyout');
    }

    public function leaveApplication()
    {
        $user = Auth::user();
        $empployeeDetails = $user->empDetail;
        $leaveTypes = leavetype::all();

        return view('pages.modules.leaveApplication', [
            'employeeDetails' => $empployeeDetails,
            'user' => $user,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function debitAdvise()
    {
        return view('pages.modules.debitAdvise');
    }

    public function checkRegister()
    {
        return view('pages.modules.checkRegister');
    }

}
