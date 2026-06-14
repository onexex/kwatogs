<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::query()->orderByDesc('id');

        if ($request->filled('model') && $request->model !== 'all') {
            $q->where('model', $request->model);
        }
        if ($request->filled('action') && $request->action !== 'all') {
            $q->where('action', $request->action);
        }
        if ($request->filled('search')) {
            $q->where('user_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $logs   = $q->paginate(25)->withQueryString();
        $models = AuditLog::select('model')->distinct()->orderBy('model')->pluck('model');

        return view('pages.management.audit_trail', compact('logs', 'models'));
    }
}
