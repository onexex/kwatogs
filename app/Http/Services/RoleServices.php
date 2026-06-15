<?php

namespace App\Http\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleServices
{
    
    public function getAllRoles()
    {
        return Role::orderBy('name')->get();
    }
    
    public function store(
        string $roleName
    ): Role
    {
        if (Role::where('name', $roleName)->exists()) {
            throw new \Exception("Role already exists.");
        }
        return Role::create(['name' => $roleName]);
    }

    public function update(
        int $roleId,
        string $roleName
    ): Role
    {
        $role = Role::findById($roleId);
        if (!$role) {
            throw new \Exception("Role not found.");
        }
        if (Role::where('name', $roleName)->where('id', '!=', $roleId)->exists()) {
            throw new \Exception("Role name already in use.");
        }
        $role->name = $roleName;
        $role->save();
        return $role;
    }

    public function assignRoleToEmployee(
        int $roleId,
        int $employeeId
    ): array
    {
        $role = Role::findById($roleId);
        if (!$role) {
            return [
                'status' => 'error',
                'message' => 'Role not found.'
            ];
        }

        $employee = User::find($employeeId);
        if (!$employee) {
            return [
                'status' => 'error',
                'message' => 'Employee not found.'
            ];
        }

            if (! $employee->hasRole($role->name)) {
            $employee->assignRole($role->name);
        }

        return [
            'status' => 'success',
            'message' => 'Role assigned successfully.'
        ];
    }

    /**
     * Assign one or more roles to one or more employees in a single action.
     *
     * @param  array<int>  $roleIds
     * @param  array<int>  $employeeIds
     */
    public function assignRolesToEmployees(
        array $roleIds,
        array $employeeIds
    ): array
    {
        $roleIds     = array_values(array_unique(array_filter($roleIds)));
        $employeeIds = array_values(array_unique(array_filter($employeeIds)));

        if (empty($roleIds) || empty($employeeIds)) {
            return [
                'status'  => 'error',
                'message' => 'Please select at least one employee and one role.',
            ];
        }

        $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->all();
        if (empty($roleNames)) {
            return [
                'status'  => 'error',
                'message' => 'Selected role(s) not found.',
            ];
        }

        $employees = User::whereIn('id', $employeeIds)->get();
        if ($employees->isEmpty()) {
            return [
                'status'  => 'error',
                'message' => 'Selected employee(s) not found.',
            ];
        }

        $assignments = 0;
        foreach ($employees as $employee) {
            foreach ($roleNames as $roleName) {
                if (! $employee->hasRole($roleName)) {
                    $employee->assignRole($roleName);
                    $assignments++;
                }
            }
        }

        $empCount  = $employees->count();
        $roleCount = count($roleNames);

        return [
            'status'  => 'success',
            'message' => $assignments > 0
                ? "Assigned {$roleCount} role(s) to {$empCount} employee(s)."
                : 'No changes — selected employee(s) already had those role(s).',
        ];
    }
}