<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $q = ErrorLog::query()->orderByDesc('id');

        if ($request->filled('type') && $request->type !== 'all') {
            $q->where('type', $request->type);
        }
        if ($request->filled('resolved') && $request->resolved !== 'all') {
            $q->where('resolved', $request->resolved === 'resolved');
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('message', 'like', '%' . $s . '%')
                  ->orWhere('user_name', 'like', '%' . $s . '%');
            });
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $logs  = $q->paginate(25)->withQueryString();
        $types = ErrorLog::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('pages.management.errorlogs', compact('logs', 'types'));
    }

    public function resolve(Request $request, ErrorLog $errorLog)
    {
        $errorLog->resolved = ! $errorLog->resolved;
        $errorLog->save();

        return response()->json(['success' => true, 'resolved' => $errorLog->resolved]);
    }
}
