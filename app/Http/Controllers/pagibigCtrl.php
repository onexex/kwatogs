<?php

// Pag-IBIG contribution schedule — CRUD on the `pagibig_contributions`
// table that payroll reads (PagibigContribution::compute()).
namespace App\Http\Controllers;

use Validator;
use App\Models\PagibigContribution;
use Illuminate\Http\Request;

class pagibigCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'range_from'         => $request->range_from,
            'range_to'           => $request->range_to,
            'employee_rate'      => $request->employee_rate,
            'employer_rate'      => $request->employer_rate,
            'employee_share'     => $request->employee_share ?? 0,
            'employer_share'     => $request->employer_share ?? 0,
            'total_contribution' => $request->total_contribution ?? 0,
            'max_salary_credit'  => $request->max_salary_credit,
            'effective_year'     => $request->effective_year,
        ];

        $validator = Validator::make($request->all(), [
            'range_from'        => 'required|numeric',
            'range_to'          => 'required|numeric|gte:range_from',
            'employee_rate'     => 'required|numeric',
            'employer_rate'     => 'required|numeric',
            'max_salary_credit' => 'required|numeric',
            'effective_year'    => 'required|integer|min:2000|max:2100',
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        if ($request->formAction == 1) {
            $saved = PagibigContribution::create($values);
        } else {
            $record = PagibigContribution::where('id', $request->updateID)->first();
            $saved  = $record ? $record->forceFill($values)->save() : false;
        }

        if ($saved) {
            return response()->json(['status' => 200, 'msg' => $request->formAction == 1 ? 'Pag-IBIG bracket created.' : 'Pag-IBIG bracket updated.']);
        }
        return response()->json(['status' => 202, 'msg' => 'Error saving record.']);
    }

    public function getall(Request $request)
    {
        $rows  = PagibigContribution::orderByDesc('effective_year')->orderBy('range_from')->get();
        $years = PagibigContribution::select('effective_year')->distinct()
            ->orderByDesc('effective_year')->pluck('effective_year');

        return response()->json(['status' => 200, 'data' => $rows, 'years' => $years]);
    }

    public function edit(Request $request)
    {
        $row = PagibigContribution::where('id', $request->id ?? $request->updateID)->get();
        return response()->json(['status' => 200, 'data' => $row]);
    }

    public function delete(Request $request)
    {
        $record = PagibigContribution::where('id', $request->id)->first();
        $ok = $record ? $record->delete() : false;
        return response()->json(['status' => $ok ? 200 : 202, 'msg' => $ok ? 'Pag-IBIG bracket deleted.' : 'Record not found.']);
    }
}
