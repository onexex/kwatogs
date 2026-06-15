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

        $employees = User::with('empDetail.department')->orderBy('lname')->get();
        $roles = Role::orderBy('name')->get();
        $users = User::with(['roles', 'empDetail.department'])->orderBy('lname')->get();

        return view('pages.management.accessrights', compact('employees', 'roles', 'users'));
    }

    public function create_update(Request $request)
    {
        // Accept both single values and arrays so the new multi-select UI
        // and any legacy single-assign callers keep working.
        $roleIds     = (array) $request->input('role_id', []);
        $employeeIds = (array) $request->input('employee_id', []);

        $response = $this->roleServices->assignRolesToEmployees($roleIds, $employeeIds);

        return redirect()->back()->with($response['status'], $response['message']);
    }

    public function removeRole(User $user, string $roleName)
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->removeRole($role);

        return redirect()->back()->with('success', 'Role removed successfully.');
    }
}