<?php

namespace Tests\Feature;

use App\Helpers\ContributionHelper;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end coverage for recurring (continuous monthly) charges.
 *
 * Runs against the live MySQL DB but every test is wrapped in a transaction
 * (DatabaseTransactions) so nothing is committed. Route middleware (custom
 * AuthCheck + employee IP gate) is disabled per test; actingAs() still sets the
 * authenticated user so the Auditable trait records the toggle.
 */
class RecurringLoanTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    /** @var string[] */
    private array $emps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->emps = User::whereNotNull('empID')->take(3)->pluck('empID')->all();
        $this->user = User::where('empID', $this->emps[0])->first();
        $this->actingAs($this->user);
    }

    private function findDetail(array $details, int $loanId): ?array
    {
        foreach ($details as $d) {
            if ((int) $d['loan_id'] === $loanId) {
                return $d;
            }
        }
        return null;
    }

    // ── HTTP: store a recurring charge ──────────────────────────────────────

    public function test_store_recurring_charge_has_no_principal_or_balance(): void
    {
        $res = $this->postJson(route('loans.store'), [
            'employee_ids'         => [$this->emps[0]],
            'loan_type'            => 'other',
            'other_description'    => 'Monthly Rent',
            'monthly_amortization' => 3000,
            'start_date'           => '2026-01-01',
            'is_recurring'         => '1',
            // deliberately NO loan_amount / end_date (form hides them)
        ]);

        $res->assertOk()->assertJson(['success' => true, 'count' => 1]);

        $loan = Loan::where('employee_id', $this->emps[0])
            ->where('other_description', 'Monthly Rent')->latest('id')->first();

        $this->assertNotNull($loan);
        $this->assertTrue($loan->is_recurring);
        $this->assertSame('active', $loan->status);
        $this->assertEquals(0.0, (float) $loan->loan_amount);
        $this->assertEquals(0.0, (float) $loan->balance);
        $this->assertNull($loan->end_date);
        $this->assertEquals(3000.0, (float) $loan->monthly_amortization);
    }

    public function test_bulk_store_creates_one_recurring_row_per_employee(): void
    {
        $res = $this->postJson(route('loans.store'), [
            'employee_ids'         => $this->emps,
            'loan_type'            => 'charges/penalty',
            'monthly_amortization' => 500,
            'start_date'           => '2026-01-01',
            'is_recurring'         => '1',
        ]);

        $res->assertOk()->assertJson(['success' => true, 'count' => count($this->emps)]);

        $rows = Loan::whereIn('employee_id', $this->emps)
            ->where('loan_type', 'charges/penalty')->where('is_recurring', true)->get();

        $this->assertCount(count($this->emps), $rows);
        $this->assertTrue($rows->every(fn ($l) => (float) $l->balance === 0.0));
    }

    public function test_government_type_cannot_be_recurring(): void
    {
        $this->postJson(route('loans.store'), [
            'employee_ids'         => [$this->emps[0]],
            'loan_type'            => 'sss',
            'loan_amount'          => 5000,
            'monthly_amortization' => 500,
            'start_date'           => '2026-01-01',
            'is_recurring'         => '1', // must be ignored for gov types
        ])->assertOk();

        $loan = Loan::where('employee_id', $this->emps[0])
            ->where('loan_type', 'sss')->latest('id')->first();

        $this->assertFalse($loan->is_recurring);
        $this->assertEquals(5000.0, (float) $loan->balance);
    }

    public function test_store_validation_requires_amount_only_when_not_recurring(): void
    {
        // Non-recurring without loan_amount → validation error
        $this->postJson(route('loans.store'), [
            'employee_ids'         => [$this->emps[0]],
            'loan_type'            => 'salary',
            'monthly_amortization' => 600,
            'start_date'           => '2026-01-01',
        ])->assertStatus(422)->assertJsonValidationErrors('loan_amount');
    }

    // ── Engine: deduction behaviour ─────────────────────────────────────────

    public function test_recurring_deducts_full_amount_every_month_without_touching_balance(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'other', 'other_description' => 'Rent',
            'loan_amount' => 0, 'balance' => 0, 'monthly_amortization' => 3000,
            'start_date' => '2026-01-01', 'status' => 'active', 'is_recurring' => true,
        ]);

        foreach (['month 1', 'month 2'] as $label) {
            $res = ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0]);
            $d = $this->findDetail($res['loan_details'], $loan->id);

            $this->assertNotNull($d, "recurring loan missing in $label");
            $this->assertEquals(3000.0, (float) $d['deducted_amount'], "wrong deduction in $label");
            $this->assertNull($d['new_balance'], "recurring should not track balance in $label");
            $this->assertTrue($d['is_recurring']);
        }

        // The loan itself never flips to paid.
        $loan->refresh();
        $this->assertSame('active', $loan->status);
    }

    public function test_finite_loan_still_diminishes_and_finishes(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'salary',
            'loan_amount' => 1000, 'balance' => 1000, 'monthly_amortization' => 600,
            'start_date' => '2026-01-01', 'status' => 'active', 'is_recurring' => false,
        ]);

        $d = $this->findDetail(
            ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0])['loan_details'],
            $loan->id
        );
        $this->assertEquals(600.0, (float) $d['deducted_amount']);   // min(600, 1000)
        $this->assertEquals(400.0, (float) $d['new_balance']);       // 1000 - 600
        $this->assertFalse($d['is_recurring']);

        // Move it near the end; the last run caps at the remaining balance.
        $loan->update(['balance' => 400]);
        $d2 = $this->findDetail(
            ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0])['loan_details'],
            $loan->id
        );
        $this->assertEquals(400.0, (float) $d2['deducted_amount']);  // min(600, 400)
        $this->assertEquals(0.0, (float) $d2['new_balance']);        // would flip to 'paid'
    }

    // ── HTTP toggle: pause / resume + audit ─────────────────────────────────

    public function test_toggle_pauses_and_resumes_and_is_audited(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'other', 'other_description' => 'Rent',
            'loan_amount' => 0, 'balance' => 0, 'monthly_amortization' => 3000,
            'start_date' => '2026-01-01', 'status' => 'active', 'is_recurring' => true,
        ]);

        $auditBefore = DB::table('audit_logs')->where('model', 'Loan')->where('model_id', $loan->id)->count();

        // OFF
        $this->postJson(route('loans.toggle', $loan->id))
            ->assertOk()->assertJson(['success' => true, 'status' => 'cancelled']);
        $this->assertSame('cancelled', $loan->fresh()->status);

        // Paused → engine skips it entirely
        $skipped = $this->findDetail(
            ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0])['loan_details'],
            $loan->id
        );
        $this->assertNull($skipped, 'cancelled recurring charge must not be deducted');

        // ON again
        $this->postJson(route('loans.toggle', $loan->id))
            ->assertOk()->assertJson(['success' => true, 'status' => 'active']);
        $this->assertSame('active', $loan->fresh()->status);

        // The status changes were captured by the audit trail (auth user present).
        $auditAfter = DB::table('audit_logs')->where('model', 'Loan')->where('model_id', $loan->id)->count();
        $this->assertGreaterThan($auditBefore, $auditAfter, 'toggle should write audit rows');
    }

    // ── Validity: start_date gating ─────────────────────────────────────────

    public function test_future_dated_recurring_charge_is_skipped_until_its_start_month(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'other', 'other_description' => 'Rent',
            'loan_amount' => 0, 'balance' => 0, 'monthly_amortization' => 3000,
            'start_date' => '2026-07-15', 'status' => 'active', 'is_recurring' => true,
        ]);

        // June 30 run — the charge starts July 15, so it must be skipped.
        $june = ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0], [], '2026-06-30');
        $this->assertNull(
            $this->findDetail($june['loan_details'], $loan->id),
            'future-dated charge must not deduct before its start month'
        );

        // July 31 run — now within validity, deducts in full.
        $july = ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0], [], '2026-07-31');
        $d = $this->findDetail($july['loan_details'], $loan->id);
        $this->assertNotNull($d, 'charge should deduct from its start month onward');
        $this->assertEquals(3000.0, (float) $d['deducted_amount']);
    }

    public function test_charge_deducts_on_the_eom_run_of_its_start_month(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'other', 'other_description' => 'Rent',
            'loan_amount' => 0, 'balance' => 0, 'monthly_amortization' => 3000,
            'start_date' => '2026-06-10', 'status' => 'active', 'is_recurring' => true,
        ]);

        // start_date 06-10 <= payDate 06-30 → deducts the same month it starts.
        $d = $this->findDetail(
            ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0], [], '2026-06-30')['loan_details'],
            $loan->id
        );
        $this->assertNotNull($d);
        $this->assertEquals(3000.0, (float) $d['deducted_amount']);
    }

    public function test_future_dated_finite_loan_is_also_skipped(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'salary',
            'loan_amount' => 1000, 'balance' => 1000, 'monthly_amortization' => 600,
            'start_date' => '2026-07-01', 'status' => 'active', 'is_recurring' => false,
        ]);

        $june = ContributionHelper::computeAll(20000, 'REG', true, $this->emps[0], [], '2026-06-30');
        $this->assertNull(
            $this->findDetail($june['loan_details'], $loan->id),
            'future-dated finite loan must not deduct before its start date'
        );
    }

    public function test_toggle_rejects_non_recurring_loan(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->emps[0], 'loan_type' => 'salary',
            'loan_amount' => 1000, 'balance' => 1000, 'monthly_amortization' => 600,
            'start_date' => '2026-01-01', 'status' => 'active', 'is_recurring' => false,
        ]);

        $this->postJson(route('loans.toggle', $loan->id))
            ->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame('active', $loan->fresh()->status); // unchanged
    }
}
