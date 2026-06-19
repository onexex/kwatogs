<?php

namespace App\Http\Controllers;

use App\Exports\AllowedIpExport;
use App\Exports\IpAccessLogExport;
use App\Http\Requests\AllowedIpRequest;
use App\Models\AllowedIp;
use App\Models\IpAccessLog;
use App\Services\IpImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AllowedIpController extends Controller
{
    private const BYPASS_ROLES = [1, 2];

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        return view('pages.management.allowed_ips.dashboard');
    }

    /**
     * AJAX endpoint that returns dashboard stats as JSON.
     * GET pages/management/allowed-ips/stats
     */
    public function stats()
    {
        $totalIps     = AllowedIp::count();
        $activeIps    = AllowedIp::where('status', true)->count();
        $blockedToday = IpAccessLog::where('status', 'blocked')
            ->whereDate('created_at', today())
            ->count();
        $allowedToday = IpAccessLog::where('status', 'allowed')
            ->whereDate('created_at', today())
            ->count();

        return response()->json(compact('totalIps', 'activeIps', 'blockedToday', 'allowedToday'));
    }

    // ─── IP Access Logs ───────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $status   = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $search   = $request->input('search');

        $logs = IpAccessLog::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($search, fn ($q) =>
                $q->where(function ($inner) use ($search) {
                    $inner->where('ip_address', 'like', "%{$search}%")
                          ->orWhere('user_name', 'like', "%{$search}%");
                })
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pages.management.allowed_ips.logs', compact('logs', 'status', 'dateFrom', 'dateTo', 'search'));
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $allowedIps = AllowedIp::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('ip_address', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages.management.allowed_ips.index', compact('allowedIps', 'search'));
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        return view('pages.management.allowed_ips.create');
    }

    public function store(AllowedIpRequest $request)
    {
        AllowedIp::create([
            'ip_address'  => $request->ip_address,
            'description' => $request->description,
            'status'      => true,
            'created_by'  => session('LoggedUserEmpID'),
        ]);

        return redirect()
            ->route('allowed-ips.index')
            ->with('success', 'IP address added to the allowlist.');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(AllowedIp $allowedIp)
    {
        return view('pages.management.allowed_ips.edit', compact('allowedIp'));
    }

    public function update(AllowedIpRequest $request, AllowedIp $allowedIp)
    {
        $allowedIp->update([
            'ip_address'  => $request->ip_address,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('allowed-ips.index')
            ->with('success', 'IP address updated successfully.');
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(AllowedIp $allowedIp)
    {
        $role = (int) session('LoggedUserRole');
        if (! in_array($role, self::BYPASS_ROLES, strict: true)) {
            return redirect()
                ->route('allowed-ips.index')
                ->with('error', 'Access denied. Only admins may delete IP entries.');
        }

        $allowedIp->delete();

        return redirect()
            ->route('allowed-ips.index')
            ->with('success', 'IP address removed from the allowlist.');
    }

    // ─── Toggle status (AJAX-aware) ───────────────────────────────────────────

    public function toggle(Request $request, AllowedIp $allowedIp)
    {
        $allowedIp->update(['status' => ! $allowedIp->status]);
        $label = $allowedIp->status ? 'enabled' : 'disabled';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status'  => $allowedIp->status,
                'label'   => ucfirst($label),
                'message' => "IP address {$label} successfully.",
            ]);
        }

        return redirect()
            ->route('allowed-ips.index')
            ->with('success', "IP address {$label} successfully.");
    }

    // ─── Bulk Actions ─────────────────────────────────────────────────────────

    public function bulkEnable(Request $request)
    {
        $ids = $this->resolveIds($request);
        AllowedIp::whereIn('id', $ids)->update(['status' => true]);

        return $this->bulkResponse($request, count($ids) . ' IP(s) enabled successfully.');
    }

    public function bulkDisable(Request $request)
    {
        $ids = $this->resolveIds($request);
        AllowedIp::whereIn('id', $ids)->update(['status' => false]);

        return $this->bulkResponse($request, count($ids) . ' IP(s) disabled successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $role = (int) session('LoggedUserRole');
        if (! in_array($role, self::BYPASS_ROLES, strict: true)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
            return redirect()->route('allowed-ips.index')->with('error', 'Access denied. Only admins may delete IP entries.');
        }

        $ids = $this->resolveIds($request);
        AllowedIp::whereIn('id', $ids)->delete();

        return $this->bulkResponse($request, count($ids) . ' IP(s) deleted successfully.');
    }

    // ─── CSV Import ───────────────────────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $service = new IpImportService();
        $result  = $service->import($request->file('csv_file'), session('LoggedUserEmpID'));

        $summary = "Import complete: {$result['inserted']} inserted, {$result['updated']} updated, {$result['skipped']} skipped.";
        $flash   = $result['skipped'] > 0 ? 'error' : 'success';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['skipped'] === 0,
                'message' => $summary,
                'result'  => $result,
            ]);
        }

        return redirect()
            ->route('allowed-ips.index')
            ->with($flash, $summary)
            ->with('import_errors', $result['errors']);
    }

    /**
     * Download the CSV import template.
     */
    public function importTemplate()
    {
        return response(IpImportService::templateContent(), 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="allowed_ips_import_template.csv"',
        ]);
    }

    // ─── Exports ──────────────────────────────────────────────────────────────

    public function exportIps(Request $request)
    {
        $search   = $request->input('search');
        $filename = 'allowed_ips_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AllowedIpExport($search), $filename);
    }

    public function exportLogs(Request $request)
    {
        $status   = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $filename = 'ip_access_logs_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new IpAccessLogExport($status, $dateFrom, $dateTo), $filename);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveIds(Request $request): array
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }
        return array_values(array_filter($ids, 'is_numeric'));
    }

    private function bulkResponse(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('allowed-ips.index')->with('success', $message);
    }
}
