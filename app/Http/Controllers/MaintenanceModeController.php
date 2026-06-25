<?php

namespace App\Http\Controllers;

use App\Models\department;
use App\Models\MaintenanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceModeController extends Controller
{
    /**
     * Settings screen: the current maintenance state + department picker.
     */
    public function index()
    {
        return view('pages.management.maintenancemode', [
            'setting'     => MaintenanceSetting::current(),
            'departments' => department::orderBy('dep_name', 'asc')->get(['id', 'dep_name']),
        ]);
    }

    /**
     * Persist the maintenance configuration (single row).
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_active'        => 'nullable|boolean',
            'scope'            => 'required|in:'.MaintenanceSetting::SCOPE_GLOBAL.','.MaintenanceSetting::SCOPE_DEPARTMENT,
            'department_ids'   => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
            'message'          => 'nullable|string|max:1000',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after_or_equal:starts_at',
        ], [
            'ends_at.after_or_equal' => 'The end date/time must be the same as or after the start.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $scope = $request->input('scope');

        // A department-scoped maintenance with no departments selected would
        // silently lock out nobody — reject it so the admin notices.
        if ($scope === MaintenanceSetting::SCOPE_DEPARTMENT
            && $request->boolean('is_active')
            && empty($request->input('department_ids'))) {
            return response()->json([
                'status' => 202,
                'msg'    => 'Select at least one department for department-scoped maintenance.',
            ]);
        }

        $setting = MaintenanceSetting::current();

        $setting->fill([
            'is_active'      => $request->boolean('is_active'),
            'scope'          => $scope,
            'department_ids' => $scope === MaintenanceSetting::SCOPE_DEPARTMENT
                ? array_values($request->input('department_ids', []))
                : [],
            'message'        => $request->input('message'),
            'starts_at'      => $request->input('starts_at') ?: null,
            'ends_at'        => $request->input('ends_at') ?: null,
            'updated_by'     => $this->actorName($request),
        ])->save();

        return response()->json([
            'status'    => 200,
            'msg'       => $setting->is_active ? 'Maintenance mode is now ACTIVE.' : 'Maintenance mode is now OFF.',
            'is_active' => $setting->is_active,
        ]);
    }

    private function actorName(Request $request): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->community_full_name
            ?: trim(($user->fname ?? '').' '.($user->lname ?? ''))
            ?: ($user->name ?? null);
    }
}
