<?php

namespace App\Http\Controllers\Roles;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Services\RoleServices;
use App\Http\Controllers\Controller;
use App\Enums\Permissions\PagePermissionsEnum;

class EmployeeRoleController extends Controller
{
    public function __construct(
        private RoleServices $roleServices
    )
    {
    }

    public function index() 
    {

        $employees = User::orderBy('lname')->get();
        $roles = Role::all();
        $users = User::with('roles')->orderBy('lname')->get();

        return view('pages.management.accessrights', compact('employees', 'roles', 'users'));
    }

    public function create_update(Request $request)
    {   
        $response = $this->roleServices->assignRoleToEmployee($request->role_id, $request->employee_id);

        return redirect()->back()->with($response['status'], $response['message']);
    }

    public function removeRole(User $user, string $roleName)
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->removeRole($role);

        return redirect()->back()->with('success', 'Role removed successfully.');
    }
}