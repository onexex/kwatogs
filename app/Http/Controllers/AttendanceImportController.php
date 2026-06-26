<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AttendanceImportService;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\Request;

class AttendanceImportController extends Controller
{
    /** Column headers — order must match AttendanceImportService::C */
    private const HEADERS = [
        'Employee ID', 'Employee Name', 'Date', 'Schedule In', 'Break Start', 'Break End',
        'Schedule Out', 'Shift Type', 'Time In', 'Time Out', 'Status', 'Total Hours',
        'Mins Late', 'Mins Undertime', 'Mins Night Diff', 'Over Break Mins', 'Outpass Mins', 'Remarks',
    ];

    public function index()
    {
        return view('pages.modules.attendance_import');
    }

    /** Download a blank template (headers + a couple of sample rows). */
    public function template()
    {
        $x = new SimpleXlsx('ATTENDANCE IMPORT');
        $widths = ['A' => 16, 'B' => 24, 'C' => 13, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12,
                   'H' => 12, 'I' => 12, 'J' => 12, 'K' => 11, 'L' => 11, 'M' => 10, 'N' => 13,
                   'O' => 14, 'P' => 14, 'Q' => 12, 'R' => 22];
        $x->setColumnWidths($widths);

        $col = 'A';
        foreach (self::HEADERS as $h) {
            $x->setString($col . '1', $h, SimpleXlsx::S_BOLD);
            $col++;
        }

        $samples = [
            ['KWTGS-2026-0011', 'JUAN DELA CRUZ', '2026-06-02', '08:00', '12:00', '13:00', '17:00', 'REGULAR', '08:03', '17:05', 'present', '', '', '', '', '', '', ''],
            ['KWTGS-2026-0005', 'PEDRO REYES', '2026-06-02', '22:00', '', '', '07:00', 'NIGHT', '22:00', '07:00', 'present', '', '', '', '', '', '', 'Overnight shift'],
            ['KWTGS-2026-0011', 'JUAN DELA CRUZ', '2026-06-03', '08:00', '12:00', '13:00', '17:00', 'REGULAR', '', '', 'absent', '0', '0', '0', '0', '0', '0', ''],
        ];
        $r = 2;
        foreach ($samples as $row) {
            $col = 'A';
            foreach ($row as $val) {
                // keep date/time-ish columns as text to preserve formatting
                $x->setString($col . $r, (string) $val, SimpleXlsx::S_TEXT);
                $col++;
            }
            $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'attendance_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Handle the uploaded file and run the import. */
    public function import(Request $request, AttendanceImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ]);

        try {
            $rows = SimpleXlsxReader::rows($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not read the file: ' . $e->getMessage()], 422);
        }

        if (count($rows) < 2) {
            return response()->json(['success' => false, 'message' => 'The file has no data rows.'], 422);
        }

        $result = $service->import($rows, $request->file('file')->getClientOriginalName());

        // Summary audit entry — one row per import (not per record), with file + counts.
        \App\Models\AuditLog::record('imported', 'Attendance', null, [
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
            'batch_id' => $result['batch_id'] ?? null,
            'message'  => $message,
        ]);
    }
}
