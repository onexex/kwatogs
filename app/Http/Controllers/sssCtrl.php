<?php

// SSS contribution schedule — CRUD on the `sss_contributions` table that
// payroll actually reads (App\Helpers\ContributionHelper / SssContribution::compute()).
namespace App\Http\Controllers;

use Validator;
use App\Models\SssContribution;
use Illuminate\Http\Request;

class sssCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'range_from'         => $request->range_from,
            'range_to'           => $request->range_to,
            'employee_share'     => $request->employee_share,
            'employer_share'     => $request->employer_share,
            'ec'                 => $request->ec ?? 0,
            'mpf'                => $request->mpf ?? 0,
            'total_contribution' => $request->total_contribution ?? 0,
            'effective_year'     => $request->effective_year,
        ];

        $validator = Validator::make($request->all(), [
            'range_from'     => 'required|numeric',
            'range_to'       => 'required|numeric|gte:range_from',
            'employee_share' => 'required|numeric',
            'employer_share' => 'required|numeric',
            'ec'             => 'nullable|numeric',
            'mpf'            => 'nullable|numeric',
            'effective_year' => 'required|integer|min:2000|max:2100',
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        if ($request->formAction == 1) {
            $saved = SssContribution::create($values);
        } else {
            // Load + save the instance so the Auditable trait records the change.
            $record = SssContribution::where('id', $request->updateID)->first();
            $saved  = $record ? $record->forceFill($values)->save() : false;
        }

        if ($saved) {
            return response()->json(['status' => 200, 'msg' => $request->formAction == 1 ? 'SSS bracket created.' : 'SSS bracket updated.']);
        }
        return response()->json(['status' => 202, 'msg' => 'Error saving record.']);
    }

    public function getall(Request $request)
    {
        $rows  = SssContribution::orderByDesc('effective_year')->orderBy('range_from')->get();
        $years = SssContribution::select('effective_year')->distinct()
            ->orderByDesc('effective_year')->pluck('effective_year');

        return response()->json(['status' => 200, 'data' => $rows, 'years' => $years]);
    }

    public function edit(Request $request)
    {
        $row = SssContribution::where('id', $request->id ?? $request->updateID)->get();
        return response()->json(['status' => 200, 'data' => $row]);
    }

    public function delete(Request $request)
    {
        $record = SssContribution::where('id', $request->id)->first();
        $ok = $record ? $record->delete() : false;
        return response()->json(['status' => $ok ? 200 : 202, 'msg' => $ok ? 'SSS bracket deleted.' : 'Record not found.']);
    }
}
