<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Exception;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database {--keep-days=7 : Number of days to retain old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a compressed backup of the database and prune old backups';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Starting database backup...');

        try {
            $path = $backupService->create();
            $this->info("Backup created: {$path}");
        } catch (Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $keepDays = (int) $this->option('keep-days');
        $deleted = $backupService->pruneOldBackups($keepDays);

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} backup(s) older than {$keepDays} day(s).");
        }

        return self::SUCCESS;
    }
}
