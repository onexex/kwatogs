<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\holidayLoggerModel;

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

        // "All Departments" / empty selection => a single row with NULL department_id.
        // Otherwise the user may pick several departments at once, producing one
        // holiday row per department so they don't have to add them one by one.
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
            // Create: one row per selected department, or a single NULL row for "All".
            $targets = ($applyToAll || empty($departmentIds)) ? [null] : $departmentIds;
            $inserted = 0;
            foreach($targets as $deptId){
                if(holidayLoggerModel::create($base + ['department_id' => $deptId])){
                    $inserted++;
                }
            }

            if($inserted){
                $msg = $inserted > 1
                    ? "Holiday added for {$inserted} departments."
                    : 'Holiday Logger Created.';
                return response()->json(['status'=>200, 'msg'=>$msg]);
            }
            return response()->json(['status'=>202, 'msg'=>'Error saving']);
        }

        // Update: a single existing row applies to one department (or All).
        // Load + save the instance (not where()->update()) so the Auditable trait fires.
        $deptId = ($applyToAll || empty($departmentIds)) ? null : $departmentIds[0];
        $record = holidayLoggerModel::where('id',$request->updateID)->first();
        if($record){
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
}
