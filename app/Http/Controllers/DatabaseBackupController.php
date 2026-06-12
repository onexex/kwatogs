<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupController extends Controller
{
    /**
     * Display the database backup management page.
     */
    public function index(DatabaseBackupService $backupService)
    {
        $backups = $backupService->list();

        return view('pages.management.databasebackup', [
            'backups' => $backups,
        ]);
    }

    /**
     * Trigger a new database backup.
     */
    public function store(Request $request, DatabaseBackupService $backupService)
    {
        abort_unless($request->user()?->can('databasebackupcreate'), 403);

        try {
            $path = $backupService->create();

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database backup created successfully: '.basename($path));
        } catch (Exception $e) {
            return redirect()
                ->route('database-backup.index')
                ->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    /**
     * Download a backup file.
     */
    public function download(string $filename)
    {
        $path = 'backups/database/'.basename($filename);

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Backup file not found.');
        }

        return Storage::disk('local')->download($path);
    }

    /**
     * Restore the database from a backup file.
     */
    public function restore(Request $request, string $filename, DatabaseBackupService $backupService)
    {
        abort_unless($request->user()?->can('databasebackuprestore'), 403);

        $path = 'backups/database/'.basename($filename);

        try {
            $backupService->restore($path);

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database restored successfully from: '.basename($filename));
        } catch (Exception $e) {
            return redirect()
                ->route('database-backup.index')
                ->with('error', 'Restore failed: '.$e->getMessage());
        }
    }

    /**
     * Delete a backup file.
     */
    public function destroy(Request $request, string $filename)
    {
        abort_unless($request->user()?->can('databasebackupdelete'), 403);

        $path = 'backups/database/'.basename($filename);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Backup deleted successfully.');
        }

        return redirect()
            ->route('database-backup.index')
            ->with('error', 'Backup file not found.');
    }
}
