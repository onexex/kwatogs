<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ScheduleImportService;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\Request;

class ScheduleImportController extends Controller
{
    /** Column headers — order must match ScheduleImportService::C */
    private const HEADERS = [
        'Employee ID', 'Employee Name', 'Start Date', 'End Date', 'Schedule In', 'Schedule Out',
        'Break Start', 'Break End', 'Shift Type', 'Days',
    ];

    public function index()
    {
        return view('pages.modules.schedule_import');
    }

    /** Download a template pre-populated with all active employees. */
    public function template()
    {
        $employees = User::whereHas('empDetail', fn($q) => $q->where('empStatus', '1'))
            ->orderBy('lname')->orderBy('fname')
            ->get(['empID', 'fname', 'lname']);

        $x = new SimpleXlsx('SCHEDULE IMPORT');
        $x->setColumnWidths(['A' => 18, 'B' => 26, 'C' => 13, 'D' => 13, 'E' => 12, 'F' => 12,
                             'G' => 12, 'H' => 12, 'I' => 14, 'J' => 26]);

        // Header row
        $col = 'A';
        foreach (self::HEADERS as $h) { $x->setString($col . '1', $h, SimpleXlsx::S_BOLD); $col++; }

        // Sample rows
        $samples = [
            ['KWTGS-2026-0011', 'DELA CRUZ, JUAN', '2026-06-01', '2026-06-30', '08:00', '17:00', '12:00', '13:00', 'REGULAR', 'Mon,Tue,Wed,Thu,Fri'],
            ['KWTGS-2026-0005', 'SANTOS, MARIA',   '2026-06-01', '2026-06-30', '22:00', '07:00', '',      '',      'NIGHT',   'Mon,Wed,Fri'],
        ];
        $r = 2;
        foreach ($samples as $row) {
            $col = 'A';
            foreach ($row as $val) { $x->setString($col . $r, (string) $val, SimpleXlsx::S_TEXT); $col++; }
            $r++;
        }

        // Pre-populate all active employees (ID + Name only; user fills in schedule columns)
        foreach ($employees as $emp) {
            $name = strtoupper($emp->lname) . ', ' . strtoupper($emp->fname);
            $x->setString('A' . $r, $emp->empID, SimpleXlsx::S_TEXT);
            $x->setString('B' . $r, $name, SimpleXlsx::S_TEXT);
            $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'schedule_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Handle the uploaded file and run the import. */
    public function import(Request $request, ScheduleImportService $service)
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

        $result = $service->import($rows, $request->file('file')->getClientOriginalName());

        // Summary audit entry — one row per import (not per record), with file + counts.
        \App\Models\AuditLog::record('imported', 'Schedule', null, [
            'file'     => $request->file('file')->getClientOriginalName(),
            'inserted' => $result['inserted'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
            'aborted'  => !empty($result['aborted']),
        ]);

        $aborted = !empty($result['aborted']);
        $message = $aborted
            ? "Import canceled — {$result['skipped']} row(s) had errors or overlaps. Fix them and re-upload; nothing was imported."
            : "Imported: {$result['inserted']} schedule day(s).";

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
