<?php

namespace App\Http\Controllers;
use DB;
use Validator;
use App\Models\company;
use Illuminate\Http\Request;

class companyCtrl extends Controller
{

    // public function create_update(Request $request){
    //     if($request->logo==""){
    //          $imageName=0;
    //     }else{
    //         $imageName= $request->logo->getClientOriginalName();
    //     }
    //     $values2 = [
    //         'comp_id'=>$request->companyid,
    //         'comp_name'=>$request->company,
    //         'comp_code'=>$request->code,
    //         'comp_color'=>$request->color,
    //         'comp_logo_path'=>$imageName,
    //     ];
    //      $values1 = [
    //         'comp_id'=>$request->companyid,
    //         'comp_name'=>$request->company,
    //         'comp_code'=>$request->code,
    //         'comp_color'=>$request->color,
    //     ];
    //     $validator = Validator::make($request->all(),[
    //         'companyid'=>'required',
    //         'company'=>'required',
    //         'code'=>'required',
    //         'color'=>'required',
    //     ]);
    //     if(!$validator->passes()){
    //         return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
    //     }else{
    //             if($request->formAction==1){
    //                 $insert = company::create($values2);
    //                 $request->logo->move('img/company/',$imageName);
    //             }else{
    //                 if($request->logo==""){
    //                     $insert = company::where('id',$request->CompanyID)->update($values1);
    //                 }else{
    //                     $insert = company::where('id',$request->CompanyID)->update($values2);
    //                 }

    //             }
    //         if($insert){
    //             if($request->formAction==1){
    //                 return response()->json(['status'=>200, 'msg'=>'Company Created.']);
    //             }else{
    //                 return response()->json(['status'=>200, 'msg'=>'Company Updated.']);
    //             }
    //         }else{
    //             return response()->json(['status'=>202, 'msg'=>'Error saving']);

    //         }
    //     }
    // }

    public function create_update(Request $request)
    {
        try {
            // 1. Validation
            $validator = Validator::make($request->all(), [
                'companyid' => 'required',
                'company'   => 'required',
                'code'      => 'required',
                'color'     => 'required',
            'logo'      => $request->formAction == 1 ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if (!$validator->passes()) {
                return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
            }

            // 2. Prepare Base Data
            $data = [
                'comp_id'    => $request->companyid,
                'comp_name'  => $request->company,
                'comp_code'  => $request->code,
                'comp_color' => $request->color,
            ];

            // 3. Handle Logo Upload (if exists)
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                // Use time() to prevent duplicate filename issues
                $imageName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('img/company/'), $imageName);
                $data['comp_logo_path'] = $imageName;
            }

            // 4. Execute Query
            if ($request->formAction == 1) {
                // Create New
                $check = company::where('comp_id', $request->companyid)->first();
                if ($check) {
                    return response()->json(['status' => 202, 'msg' => 'Company ID already exists!']);
                }
                
                $query = company::create($data);
                $message = 'Company Created Successfully.';
            } else {
                // Update Existing
                $query = company::where('id', $request->CompanyID)->update($data);
                $message = 'Company Updated Successfully.';
            }

            if ($query) {
                return response()->json(['status' => 200, 'msg' => $message]);
            } else {
                return response()->json(['status' => 202, 'msg' => 'No changes were saved.']);
            }

        } catch (\Exception $e) {
            // Catch any database or file errors
            return response()->json([
                'status' => 500,
                'msg' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    public function get_all(Request $request){

        $getComp= company::get();
        if($getComp){
            return response()->json(['status'=>200, 'data'=>$getComp]);
        }
    }

    public function get_edit(Request $request){
        $CompanyID=$request->CompanyID;
        $getCompany = company::where('id', $CompanyID)->get();
        return response()->json(['status'=>200, 'data'=>$getCompany]);
    }

    public function delete(Request $request){
        //1 super user
        //2 admin
        //3 normal user

        $user=session()->get('LoggedUserID');
        $role=session()->get('LoggedUserRole');
        $id=$request->id;

        if($role!=1){
            return response()->json(['status'=>200, 'msg'=>'Access Denied. Required superuser access for this request!']);
        }else{
            $getCompany = company::where('id', $id)->delete();
            return response()->json(['status'=>200, 'msg'=>"Company deleted successfully."]);
        }


    }




}
