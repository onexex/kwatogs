<?php

namespace App\Http\Controllers;

use App\Services\OvertimeImportService;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\Request;

class OvertimeImportController extends Controller
{
    private const HEADERS = [
        'Employee ID', 'Employee Name', 'Date From', 'Date To', 'Time In', 'Time Out',
        'Day Type', 'Purpose', 'Status', 'Total Hrs', 'Total Pay', 'Hourly Rate',
    ];

    public function index()
    {
        return view('pages.modules.overtime_import');
    }

    public function template()
    {
        $x = new SimpleXlsx('OVERTIME IMPORT');
        $x->setColumnWidths(['A' => 16, 'B' => 24, 'C' => 13, 'D' => 13, 'E' => 11, 'F' => 11,
                             'G' => 24, 'H' => 26, 'I' => 16, 'J' => 11, 'K' => 12, 'L' => 12]);
        $col = 'A';
        foreach (self::HEADERS as $h) { $x->setString($col . '1', $h, SimpleXlsx::S_BOLD); $col++; }

        $samples = [
            ['KWTGS-2026-0011', 'JUAN DELA CRUZ', '2026-06-02', '2026-06-02', '18:00', '21:00', 'regular', 'Month-end reports', 'APPROVEDBYCFO', '', '', ''],
            ['KWTGS-2026-0005', 'PEDRO REYES', '2026-06-08', '2026-06-08', '08:00', '17:00', 'rest_day', 'Rest day duty', 'APPROVEDBYCFO', '', '', ''],
        ];
        $r = 2;
        foreach ($samples as $row) {
            $col = 'A';
            foreach ($row as $val) { $x->setString($col . $r, (string) $val, SimpleXlsx::S_TEXT); $col++; }
            $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'overtime_import_template.xlsx', [
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

        $service = new OvertimeImportService(optional(optional($request->user())->empDetail)->id);
        $result = $service->import($rows);

        // Summary audit entry — one row per import (not per record), with file + counts.
        \App\Models\AuditLog::record('imported', 'Overtime', null, [
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
