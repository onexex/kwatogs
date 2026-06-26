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
     * Import an external SQL dump (uploaded from outside) into the database,
     * either replacing or merging with the current data.
     */
    public function import(Request $request, DatabaseBackupService $backupService)
    {
        abort_unless($request->user()?->can('databasebackuprestore'), 403);

        $request->validate([
            'sql_file' => ['required', 'file', 'max:1048576'], // up to 1 GB
            'mode' => ['required', 'in:replace,merge-skip,merge-overwrite'],
        ]);

        $file = $request->file('sql_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = strtolower($file->getClientOriginalName());

        // Accept .sql and .sql.gz (the gzip case has a .gz extension but a .sql.gz name).
        $isSql = $extension === 'sql';
        $isGz = $extension === 'gz' && str_ends_with($originalName, '.sql.gz');

        if (!$isSql && !$isGz) {
            return redirect()
                ->route('database-backup.index')
                ->with('error', 'Invalid file type. Upload a .sql or .sql.gz dump.');
        }

        try {
            $storedName = sprintf('import_%s.%s', now()->format('Y-m-d_H-i-s'), $isGz ? 'sql.gz' : 'sql');
            $path = $file->storeAs('backups/database', $storedName, 'local');

            $backupService->import($path, $request->input('mode'));

            // Clean up the uploaded dump once it has been applied.
            Storage::disk('local')->delete($path);

            $labels = [
                'replace' => 'replaced',
                'merge-skip' => 'merged (kept existing rows on conflict)',
                'merge-overwrite' => 'merged (imported rows won on conflict)',
            ];

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database '.$labels[$request->input('mode')].' successfully from the uploaded file.');
        } catch (Exception $e) {
            if (isset($path)) {
                Storage::disk('local')->delete($path);
            }

            return redirect()
                ->route('database-backup.index')
                ->with('error', 'Import failed: '.$e->getMessage());
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
