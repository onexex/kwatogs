<?php

namespace Tests\Feature;

use App\Http\Controllers\PayrollController;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Payroll generation is hard-blocked while a DIFFERENT pay date has payroll pending
 * approval (generated but no payroll_approvals row). The same pay date stays
 * recompute-able, and an approved pay date no longer counts as pending.
 */
class PayrollPendingBlockTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp(); // DatabaseTransactions has begun the rollback-wrapped transaction by now
        // Clean slate so the gate only sees rows this test seeds (deletes roll back with the tx).
        Payroll::query()->delete();
        PayrollApproval::query()->delete();
    }

    private function seedPayroll(string $payDate): void
    {
        Payroll::create([
            'employee_id'        => 'PR-EMP-001',
            'payroll_start_date' => '2030-05-26',
            'payroll_end_date'   => '2030-06-10',
            'pay_date'           => $payDate,
            'status'             => 'pending',
        ]);
    }

    private function generate(string $payDate, bool $canRegenerate = false)
    {
        $req = Request::create('/payroll/compute', 'GET', [
            'date_from' => '2030-06-11',
            'date_to'   => '2030-06-25',
            'pay_date'  => $payDate,
        ]);
        if ($canRegenerate) {
            // Stand-in user whose can() grants the regeneratepayroll override.
            $req->setUserResolver(fn() => new class {
                public function can($ability) { return $ability === 'regeneratepayroll'; }
            });
        }
        return app(PayrollController::class)->computePayroll($req);
    }

    private function isPendingBlock($resp): bool
    {
        return $resp instanceof JsonResponse
            && $resp->getStatusCode() === 423
            && (($resp->getData(true)['validation'] ?? null) === 'pending_payroll');
    }

    public function test_generation_blocked_when_another_pay_date_pending(): void
    {
        $this->seedPayroll('2030-06-15'); // pending (unapproved), different pay date

        $resp = $this->generate('2030-06-30'); // try to generate a NEW pay date

        $this->assertTrue($this->isPendingBlock($resp), 'should hard-block on other pending pay date');
    }

    public function test_not_blocked_when_pending_is_approved(): void
    {
        $this->seedPayroll('2030-06-15');
        PayrollApproval::create([
            'pay_date'         => '2030-06-15',
            'approved_by_name' => 'Test Approver',
            'approved_at'      => now(),
        ]);

        $resp = $this->generate('2030-06-30');

        $this->assertFalse($this->isPendingBlock($resp), 'approved pay date must not count as pending');
    }

    public function test_same_pay_date_recompute_not_blocked(): void
    {
        $this->seedPayroll('2030-06-30'); // pending for the SAME date being regenerated

        $resp = $this->generate('2030-06-30');

        $this->assertFalse($this->isPendingBlock($resp), 'recomputing the same pay date must be allowed');
    }

    public function test_regeneratepayroll_permission_overrides_block(): void
    {
        $this->seedPayroll('2030-06-15'); // pending, different pay date

        $resp = $this->generate('2030-06-30', canRegenerate: true);

        $this->assertFalse($this->isPendingBlock($resp), 'regeneratepayroll must override the pending block');
    }
}
