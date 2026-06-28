<?php

// JMC
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\holidayLoggerModel;
use App\Models\department;

class holidayLoggerCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'date'=>'required',
            'description'=>'required',
            'type'=>'required',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }

        $applyToAll = $request->boolean('all_departments');
        $departmentIds = $request->input('department_ids', []);
        if (!is_array($departmentIds)) {
            $departmentIds = array_filter([$departmentIds], fn($v) => $v !== '' && $v !== null);
        }
        $departmentIds = array_values(array_unique(array_filter($departmentIds, fn($v) => $v !== '' && $v !== null)));

        $base = [
            'date'        => $request->date,
            'description' => $request->description,
            'type'        => $request->type,
        ];

        if($request->formAction==1){
            // "All Departments" => one row per every department (never null).
            $targets = $applyToAll
                ? department::pluck('id')->toArray()
                : $departmentIds;

            if (empty($targets)) {
                return response()->json(['status'=>201, 'error'=>['department_ids'=>['Please select at least one department.']]]);
            }

            // Pre-fetch existing records for this date + type + description across all targets to avoid N+1.
            $existing = holidayLoggerModel::where('date', $request->date)
                ->where('type', $request->type)
                ->where('description', $request->description)
                ->whereIn('department_id', $targets)
                ->pluck('department_id')
                ->map(fn($v) => (string) $v)
                ->flip()
                ->all();

            $inserted = 0;
            $skipped  = 0;
            foreach ($targets as $deptId) {
                if (isset($existing[(string) $deptId])) {
                    $skipped++;
                    continue;
                }
                if (holidayLoggerModel::create($base + ['department_id' => $deptId])) {
                    $inserted++;
                }
            }

            if ($inserted === 0 && $skipped > 0) {
                return response()->json(['status'=>202, 'msg'=>'Holiday already exists for the selected department(s) on this date and type.']);
            }
            if ($inserted) {
                $msg = $inserted > 1
                    ? "Holiday added for {$inserted} department(s)." . ($skipped ? " {$skipped} already existed and were skipped." : '')
                    : 'Holiday Logger Created.' . ($skipped ? " {$skipped} department(s) already had this holiday." : '');
                return response()->json(['status'=>200, 'msg'=>$msg]);
            }
            return response()->json(['status'=>202, 'msg'=>'Error saving']);
        }

        // Update: a single existing row.
        $deptId = empty($departmentIds) ? null : $departmentIds[0];
        $record = holidayLoggerModel::where('id', $request->updateID)->first();
        if ($record) {
            // Block if another row already has the same date + type + department.
            $duplicate = holidayLoggerModel::where('date', $request->date)
                ->where('type', $request->type)
                ->where('description', $request->description)
                ->where('department_id', $deptId)
                ->where('id', '!=', $request->updateID)
                ->exists();
            if ($duplicate) {
                return response()->json(['status'=>202, 'msg'=>'A holiday with the same date, type, and department already exists.']);
            }
            $record->forceFill($base + ['department_id' => $deptId])->save();
            return response()->json(['status'=>200, 'msg'=>'Holiday Logger Updated.']);
        }
        return response()->json(['status'=>202, 'msg'=>'Error saving']);
    }

   public function getall(Request $request)
    {
        // Get the current year
        $currentYear = date('Y');

        // Filter by the 'date' column (or whichever column stores your holiday date)
        $getAll = holidayLoggerModel::whereYear('date', $currentYear)
                    ->with('department') // Eager load the related department data
                    ->orderBy('date', 'asc') // Usually better to sort by the actual holiday date
                    ->get();

        if ($getAll) {
            return response()->json([
                'status' => 200, 
                'data' => $getAll,
                'year' => $currentYear // Optional: helpful for the frontend to know the year
            ]);
        }

        return response()->json(['status' => 404, 'message' => 'No data found']);
    }

    public function edit(Request $request){
        $getEOVal = holidayLoggerModel::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }

    public function delete(Request $request){
        $record = holidayLoggerModel::where('id', $request->id)->first();
        if($record){
            $record->delete();
            return response()->json(['status'=>200, 'msg'=>'Holiday deleted.']);
        }
        return response()->json(['status'=>202, 'msg'=>'Record not found.']);
    }
}
