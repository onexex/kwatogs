<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\ThirteenthMonthController;
use App\Models\ThirteenthMonthPayout;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The mid-year HALF advance on ThirteenthMonthController@release is the 13th
 * month accrued over the FIRST HALF of the coverage window, paid IN FULL — the
 * company's "50% in June, 50% in December" split. Because 13th month accrues
 * evenly with basic pay, the first half ≈ 50% of the full-year figure and the
 * December FULL release pays the remaining ≈ 50%; the two always sum to the
 * exact full-year 13th month.
 */
class ThirteenthMonthHalfAdvanceTest extends TestCase
{
    use DatabaseTransactions;

    /** Clean, collision-proof fixture year (no real payroll data lives here). */
    private const YEAR = 2099;
    private const EMP  = 'ZZ-13TH-1';

    /**
     * Twelve monthly payrolls (Jan–Dec, pay date the 15th), ₱20,000 earned
     * basic each ⇒ full-year total ₱240,000 ⇒ 13th month ₱20,000. The window
     * midpoint (Jan 1 + 182 days = Jul 2) splits it into Jan–Jun (6 rows,
     * ₱120,000 ⇒ ₱10,000 = the June half) and Jul–Dec (the December half).
     */
    private function seedEmployee(): void
    {
        DB::table('users')->updateOrInsert(
            ['empID' => self::EMP],
            [
                'fname'    => 'Thirteenth',
                'lname'    => 'Tester',
                'email'    => 'zz-13th-1@test.local',
                'password' => bcrypt('secret123'),
            ]
        );

        for ($m = 1; $m <= 12; $m++) {
            $payDate = sprintf('%d-%02d-15', self::YEAR, $m);
            DB::table('payrolls')->insert([
                'employee_id'        => self::EMP,
                'payroll_start_date' => sprintf('%d-%02d-01', self::YEAR, $m),
                'payroll_end_date'   => $payDate,
                'pay_date'           => $payDate,
                'gross_pay'          => 20000,
                'overtime_pay'       => 0,
                'holiday_pay'        => 0,
                'night_diff_pay'     => 0,
            ]);
        }
    }

    private function fullYearPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_ids'  => [self::EMP],
            'coverage_from' => self::YEAR.'-01-01',
            'coverage_to'   => self::YEAR.'-12-31',
        ], $overrides);
    }

    private function release(array $payload)
    {
        $req = Request::create('/reports/thirteenth-month/release', 'POST', $payload);
        return app(ThirteenthMonthController::class)->release($req);
    }

    private function row(string $portion): ?ThirteenthMonthPayout
    {
        return ThirteenthMonthPayout::where('employee_id', self::EMP)
            ->where('coverage_year', self::YEAR)
            ->where('portion', $portion)
            ->first();
    }

    public function test_half_pays_the_first_half_accrual_in_full(): void
    {
        $this->seedEmployee();

        // Full-year window selected; "half" must still split at the midpoint.
        $resp = $this->release($this->fullYearPayload(['portion' => 'half']));
        $this->assertSame('ok', $resp->getData(true)['status']);

        $row = $this->row(ThirteenthMonthPayout::PORTION_HALF);
        $this->assertNotNull($row, 'a half payout row must be recorded');

        // Jan–Jun accrual = ₱120,000 ÷ 12 = ₱10,000 = 50% of the ₱20,000 full
        // year — NOT the full ₱20,000 (would mean it ignored the midpoint cap)
        // and NOT ₱10,000-via-÷2 of a partial figure.
        $this->assertEqualsWithDelta(10000.0, (float) $row->amount, 0.01);

        // The row stores the FIRST-HALF window (start → date midpoint).
        $expectedMid = Carbon::create(self::YEAR, 1, 1)->addDays(182)->format('Y-m-d');
        $this->assertSame(self::YEAR.'-01-01', $row->coverage_from->format('Y-m-d'));
        $this->assertSame($expectedMid, $row->coverage_to->format('Y-m-d'));
    }

    public function test_full_release_pays_the_remaining_half(): void
    {
        $this->seedEmployee();

        $this->release($this->fullYearPayload(['portion' => 'half']));
        $this->release($this->fullYearPayload(['portion' => 'full']));

        // December remainder = full-year ₱20,000 − June ₱10,000 = ₱10,000.
        $this->assertEqualsWithDelta(10000.0, (float) $this->row(ThirteenthMonthPayout::PORTION_FULL)->amount, 0.01);
    }

    public function test_the_two_halves_sum_to_the_full_year_thirteenth(): void
    {
        $this->seedEmployee();

        $this->release($this->fullYearPayload(['portion' => 'half']));
        $this->release($this->fullYearPayload(['portion' => 'full']));

        $total = (float) ThirteenthMonthPayout::where('employee_id', self::EMP)
            ->where('coverage_year', self::YEAR)
            ->sum('amount');

        // ₱10,000 (June) + ₱10,000 (December) = ₱20,000 (the full 13th month).
        $this->assertEqualsWithDelta(20000.0, $total, 0.01);
    }
}
