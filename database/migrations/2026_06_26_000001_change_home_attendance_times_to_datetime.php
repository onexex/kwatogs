<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Convert the punch columns from TIMESTAMP to DATETIME.
     *
     * TIMESTAMP is stored as UTC and converted to/from the MySQL session timezone on every
     * read/write, and `mysqldump` defaults to `--tz-utc` (rewriting TIMESTAMP values to UTC in
     * the dump). With the online host and local server on different timezones, that shifted every
     * attendance time on backup/restore. DATETIME is stored literally — MySQL never tz-converts it
     * and mysqldump never rewrites it — so the wall-clock time stays correct across servers.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE home_attendances MODIFY time_in DATETIME NULL');
        DB::statement('ALTER TABLE home_attendances MODIFY time_out DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE home_attendances MODIFY time_in TIMESTAMP NULL');
        DB::statement('ALTER TABLE home_attendances MODIFY time_out TIMESTAMP NULL');
    }
};
