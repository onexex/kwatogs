<?php

namespace App\Http\Controllers;

use App\Services\LeaveImportService;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\Request;

class LeaveImportController extends Controller
{
    private const HEADERS = [
        'Employee ID', 'Employee Name', 'Leave Type', 'Start Date', 'End Date',
        'Leave Kind', 'Half Day', 'Reason', 'Status', 'Hours Per Day',
    ];

    public function index()
    {
        return view('pages.modules.leave_import');
    }

    public function template()
    {
        $x = new SimpleXlsx('LEAVE IMPORT');
        $x->setColumnWidths(['A' => 16, 'B' => 24, 'C' => 20, 'D' => 13, 'E' => 13,
                             'F' => 11, 'G' => 10, 'H' => 26, 'I' => 16, 'J' => 12]);
        $col = 'A';
        foreach (self::HEADERS as $h) { $x->setString($col . '1', $h, SimpleXlsx::S_BOLD); $col++; }

        $samples = [
            ['KWTGS-2026-0011', 'JUAN DELA CRUZ', 'Vacation Leave', '2026-06-04', '2026-06-05', 'Paid', '', 'Family matter', 'APPROVEDBYCFO', ''],
            ['KWTGS-2026-0060', 'MARIA SANTOS', 'Sick Leave', '2026-06-06', '2026-06-06', 'Paid', 'TRUE', 'Half-day checkup', 'APPROVEDBYCFO', ''],
        ];
        $r = 2;
        foreach ($samples as $row) {
            $col = 'A';
            foreach ($row as $val) { $x->setString($col . $r, (string) $val, SimpleXlsx::S_TEXT); $col++; }
            $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'leave_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv,txt|max:10240']);

        try {
            $rows = SimpleXlsxReader::rows($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not read the file: ' . $e->getMessage()], 422);
        }
        if (count($rows) < 2) {
            return response()->json(['success' => false, 'message' => 'The file has no data rows.'], 422);
        }

        $service = new LeaveImportService(optional($request->user())->id);
        $result = $service->import($rows);

        // Summary audit entry — one row per import (not per record), with file + counts.
        \App\Models\AuditLog::record('imported', 'Leave', null, [
            'file'     => $request->file('file')->getClientOriginalName(),
            'inserted' => $result['inserted'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
            'aborted'  => !empty($result['aborted']),
        ]);

        $aborted = !empty($result['aborted']);
        $message = $aborted
            ? "Import canceled — {$result['skipped']} row(s) had errors or duplicates. Fix them and re-upload; nothing was imported."
            : "Imported: {$result['inserted']} new, {$result['updated']} updated.";

        return response()->json([
            'success'  => true,
            'aborted'  => $aborted,
            'inserted' => $result['inserted'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
            'errors'   => $result['errors'],
            'message'  => $message,
        ]);
    }
}
