<?php

// PhilHealth contribution schedule — CRUD on the `philhealth_contributions`
// table that payroll reads (PhilhealthContribution::compute()).
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\PhilhealthContribution;

class philhealthCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'range_from'     => $request->range_from,
            'range_to'       => $request->range_to,
            'premium_rate'   => $request->premium_rate,
            'employee_share' => $request->employee_share,
            'employer_share' => $request->employer_share,
            'min_salary'     => $request->min_salary,
            'max_salary'     => $request->max_salary,
            'effective_year' => $request->effective_year,
        ];

        $validator = Validator::make($request->all(), [
            'range_from'     => 'required|numeric',
            'range_to'       => 'required|numeric|gte:range_from',
            'premium_rate'   => 'required|numeric',
            'employee_share' => 'required|numeric',
            'employer_share' => 'required|numeric',
            'min_salary'     => 'required|numeric',
            'max_salary'     => 'required|numeric|gte:min_salary',
            'effective_year' => 'required|integer|min:2000|max:2100',
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        if ($request->formAction == 1) {
            $saved = PhilhealthContribution::create($values);
        } else {
            $record = PhilhealthContribution::where('id', $request->updateID)->first();
            $saved  = $record ? $record->forceFill($values)->save() : false;
        }

        if ($saved) {
            return response()->json(['status' => 200, 'msg' => $request->formAction == 1 ? 'PhilHealth bracket created.' : 'PhilHealth bracket updated.']);
        }
        return response()->json(['status' => 202, 'msg' => 'Error saving record.']);
    }

    public function getall(Request $request)
    {
        $rows  = PhilhealthContribution::orderByDesc('effective_year')->orderBy('range_from')->get();
        $years = PhilhealthContribution::select('effective_year')->distinct()
            ->orderByDesc('effective_year')->pluck('effective_year');

        return response()->json(['status' => 200, 'data' => $rows, 'years' => $years]);
    }

    public function edit(Request $request)
    {
        $row = PhilhealthContribution::where('id', $request->id ?? $request->updateID)->get();
        return response()->json(['status' => 200, 'data' => $row]);
    }

    public function delete(Request $request)
    {
        $record = PhilhealthContribution::where('id', $request->id)->first();
        $ok = $record ? $record->delete() : false;
        return response()->json(['status' => $ok ? 200 : 202, 'msg' => $ok ? 'PhilHealth bracket deleted.' : 'Record not found.']);
    }
}
