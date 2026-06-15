<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\SssContributionSeeder;
use App\Models\SssContribution;

/**
 * Loads the official 2026 SSS contribution brackets into sss_contributions
 * on deployment. The seeder is idempotent (it clears existing 2026 rows first),
 * so this is safe to run more than once. Older-year rows are left untouched —
 * the compute() lookup picks the latest effective_year, so 2026 takes priority.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new SssContributionSeeder())->run();
    }

    public function down(): void
    {
        SssContribution::where('effective_year', 2026)->delete();
    }
};
