<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * The storage disk where backups are kept.
     *
     * @var string
     */
    protected string $disk = 'local';

    /**
     * The directory (relative to the disk root) where backups are stored.
     *
     * @var string
     */
    protected string $backupPath = 'backups/database';

    /**
     * Create a new database backup (gzip compressed SQL dump).
     *
     * @return string The relative path to the created backup file.
     *
     * @throws Exception
     */
    public function create(): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new Exception("Database backup only supports the 'mysql' driver. Current driver: {$config['driver']}");
        }

        $this->ensureBackupDirectoryExists();

        $filename = sprintf(
            '%s_%s.sql.gz',
            $config['database'],
            now()->format('Y-m-d_H-i-s')
        );

        $disk = Storage::disk($this->disk);
        $absoluteDirectory = $disk->path($this->backupPath);
        $sqlPath = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename.'.tmp.sql';
        $gzPath = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;

        $process = new Process($this->buildDumpCommand($config, $sqlPath));
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            @unlink($sqlPath);
            throw new Exception('Database backup failed: '.($process->getErrorOutput() ?: $process->getOutput()));
        }

        if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);
            throw new Exception('Database backup file was not created or is empty.');
        }

        $this->gzipFile($sqlPath, $gzPath);
        @unlink($sqlPath);

        if (!file_exists($gzPath) || filesize($gzPath) === 0) {
            throw new Exception('Database backup file was not created or is empty.');
        }

        Log::info("Database backup created successfully: {$filename}");

        return $this->backupPath.'/'.$filename;
    }

    /**
     * Restore the database from a backup file (.sql or .sql.gz).
     *
     * @param string $relativePath Path to the backup file, relative to the backup disk.
     * @return void
     *
     * @throws Exception
     */
    public function restore(string $relativePath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new Exception("Database restore only supports the 'mysql' driver. Current driver: {$config['driver']}");
        }

        $disk = Storage::disk($this->disk);

        if (!$disk->exists($relativePath)) {
            throw new Exception('Backup file not found.');
        }

        $absolutePath = $disk->path($relativePath);
        $sqlPath = $absolutePath;
        $temporarySqlFile = false;

        if (str_ends_with($absolutePath, '.gz')) {
            $sqlPath = preg_replace('/\.gz$/', '', $absolutePath).'.restore.sql';
            $this->gunzipFile($absolutePath, $sqlPath);
            $temporarySqlFile = true;
        }

        try {
            if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
                throw new Exception('Backup file is empty or could not be read.');
            }

            $process = new Process($this->buildRestoreCommand($config, $sqlPath));
            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception('Database restore failed: '.($process->getErrorOutput() ?: $process->getOutput()));
            }

            Log::info('Database restored successfully from: '.basename($relativePath));
        } finally {
            if ($temporarySqlFile) {
                @unlink($sqlPath);
            }
        }
    }

    /**
     * Build the mysql restore command, importing from the given SQL file.
     *
     * @param array $config
     * @param string $sourceFile
     * @return array<int, string>
     */
    protected function buildRestoreCommand(array $config, string $sourceFile): array
    {
        $command = [
            $this->resolveMysqlBinary(),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? ''),
        ];

        if (!empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $command[] = $config['database'] ?? '';
        $command[] = '--execute=source '.$sourceFile;

        return $command;
    }

    /**
     * Resolve the path to the mysql binary.
     *
     * Checks the MYSQL_PATH env variable first, then falls back to common
     * installation locations (e.g. XAMPP on Windows), and finally assumes
     * `mysql` is available on the system PATH.
     *
     * @return string
     */
    protected function resolveMysqlBinary(): string
    {
        $configured = env('MYSQL_PATH');

        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            '/opt/lampp/bin/mysql',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Fall back to relying on PATH.
        return 'mysql';
    }

    /**
     * Decompress a gzip file.
     *
     * @param string $source
     * @param string $destination
     * @return void
     *
     * @throws Exception
     */
    protected function gunzipFile(string $source, string $destination): void
    {
        $in = gzopen($source, 'rb');
        $out = fopen($destination, 'wb');

        if ($in === false || $out === false) {
            throw new Exception('Unable to open file for gzip decompression.');
        }

        while (!gzeof($in)) {
            fwrite($out, gzread($in, 1024 * 512));
        }

        gzclose($in);
        fclose($out);
    }

    /**
     * Build the mysqldump command, dumping output to the given SQL file.
     *
     * The command is returned as an array so it can be passed directly to
     * Symfony Process, avoiding shell quoting/piping issues (especially on
     * Windows, where there is no built-in `gzip`).
     *
     * @param array $config
     * @param string $destination
     * @return array<int, string>
     */
    protected function buildDumpCommand(array $config, string $destination): array
    {
        $command = [
            $this->resolveMysqldumpBinary(),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? ''),
        ];

        if (!empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $command[] = '--single-transaction';
        $command[] = '--quick';
        $command[] = '--routines';
        $command[] = '--triggers';
        $command[] = '--result-file='.$destination;
        $command[] = $config['database'] ?? '';

        return $command;
    }

    /**
     * Resolve the path to the mysqldump binary.
     *
     * Checks the MYSQLDUMP_PATH env variable first, then falls back to
     * common installation locations (e.g. XAMPP on Windows), and finally
     * assumes `mysqldump` is available on the system PATH.
     *
     * @return string
     */
    protected function resolveMysqldumpBinary(): string
    {
        $configured = env('MYSQLDUMP_PATH');

        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Fall back to relying on PATH.
        return 'mysqldump';
    }

    /**
     * Compress a file using gzip (via PHP's zlib extension).
     *
     * @param string $source
     * @param string $destination
     * @return void
     *
     * @throws Exception
     */
    protected function gzipFile(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb9');

        if ($in === false || $out === false) {
            throw new Exception('Unable to open file for gzip compression.');
        }

        while (!feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }

        fclose($in);
        gzclose($out);
    }

    /**
     * Delete backup files older than the given number of days.
     *
     * @param int $days
     * @return int Number of files deleted.
     */
    public function pruneOldBackups(int $days = 7): int
    {
        $disk = Storage::disk($this->disk);
        $deleted = 0;
        $cutoff = now()->subDays($days)->getTimestamp();

        foreach ($disk->files($this->backupPath) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            Log::info("Pruned {$deleted} old database backup(s) older than {$days} day(s).");
        }

        return $deleted;
    }

    /**
     * List all available backup files, most recent first.
     *
     * @return array<int, array{path: string, size: int, last_modified: int}>
     */
    public function list(): array
    {
        $disk = Storage::disk($this->disk);

        $files = collect($disk->files($this->backupPath))
            ->map(fn (string $file) => [
                'path' => $file,
                'size' => $disk->size($file),
                'last_modified' => $disk->lastModified($file),
            ])
            ->sortByDesc('last_modified')
            ->values()
            ->all();

        return $files;
    }

    /**
     * Ensure the backup directory exists on the configured disk.
     *
     * @return void
     */
    protected function ensureBackupDirectoryExists(): void
    {
        $disk = Storage::disk($this->disk);

        if (!$disk->exists($this->backupPath)) {
            $disk->makeDirectory($this->backupPath);
        }
    }
}
