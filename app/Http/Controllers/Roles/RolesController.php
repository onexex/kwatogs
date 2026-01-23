<?php

namespace App\Http\Controllers\Roles;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Services\RoleServices;
use App\Http\Controllers\Controller;
use App\Enums\Permissions\PagePermissionsEnum;
use App\Enums\Permissions\OvertimePermissionEnum;

class RolesController extends Controller
{
    public function __construct(
        private RoleServices $roleServices
    )
    {
    }

    public function index()
    {
        $roles = $this->roleServices->getAllRoles();
        return view('pages.users.roles', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required|string|max:255',
        ]);

        try {
            $this->roleServices->store(
                roleName: $request->input('role')
            );

            return redirect()->back()->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, Role $user_role)
    {
        $request->validate([
            'role' => 'required|string|max:255',
        ]);

        try {
            $role = $this->roleServices->update(
                roleId: $user_role->id,
                roleName: $request->input('role')
            );

            return redirect()->back()->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // public function show(Role $user_role, Request $request)
    // {
    //     $permissionEnums = [];

    //     if ($request->permission) {
    //         if ($request->permission == 'page') {
    //             $permissionEnums = [
    //                 'Page Permissions' => PagePermissionsEnum::class,
    //             ];
    //         } else if ($request->permission == 'overtime') {
    //             $permissionEnums = [
    //                 'Overtime Permissions' => OvertimePermissionEnum::class,
    //             ];
    //         } else {
    //             $permissionEnums = [
    //                 'Page Permissions' => PagePermissionsEnum::class,
    //             ];
    //         }

    //     }

    //     $permissions = collect($permissionEnums)->map(function ($enumClass, $title) {
    //         return [
    //             'title' => $title,
    //             'permissions' => $enumClass::toArray(),
    //         ];
    //     })->values()->toArray();
        
    //     return view('pages.users.role_permission', [
    //         'permissions' => $permissions,
    //         'role' => $user_role,   
    //         'permissiontab' => $request->permission ?? '',
    //     ]);
    // }

    public function show(Role $user_role, Request $request)
{
    $permissionEnums = [];

    if ($request->permission == 'overtime') {
        $permissionEnums = [
            'Overtime Permissions' => OvertimePermissionEnum::class,
        ];
    } else {
        // Default to Page Permissions
        $permissionEnums = [
            'Page Permissions' => PagePermissionsEnum::class,
        ];
    }

    $permissions = collect($permissionEnums)->map(function ($enumClass, $title) {
        // 1. Get the array from the Enum
        $data = $enumClass::toArray(); 

        // 2. Sort the array alphabetically by value
        asort($data); 

        return [
            'title' => $title,
            'permissions' => $data,
        ];
    })->values()->toArray();
    
    return view('pages.users.role_permission', [
        'permissions' => $permissions,
        'role' => $user_role,   
        'permissiontab' => $request->permission ?? 'page', // Default tab text
    ]);
}

    public function addPermission(Role $role, Request $request)
    {
        $role->givePermissionTo($request->permission);
        return response()->json(['status' => 'added']);
    }

    public function removePermission(Role $role, Request $request)
    {
        $role->revokePermissionTo($request->permission);
        return response()->json(['status' => 'removed']);
    }
}