<?php

namespace Tests\Feature;

use App\Enums\OvertimeStatusEnum;
use App\Http\Controllers\Overtime\AdminOvertimeController;
use App\Models\empDetail;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AdminOvertimeController@store files on behalf of an employee. The resulting
 * status is permission-driven: a filer holding `approveovertime` files APPROVED,
 * a supervisor (holding `adminovertime` only) files FOR APPROVAL. A supervisor
 * is also confined to their own department, re-checked server-side.
 */
class AdminOvertimeSupervisorTest extends TestCase
{
    use DatabaseTransactions;

    private const DEP_A = 16;
    private const DEP_B = 14;
    private const COMP  = '01';

    private function makeEmployee(string $empID, int $depID): User
    {
        $user = User::create([
            'empID'    => $empID,
            'fname'    => 'Test',
            'lname'    => 'Employee' . $empID,
            'email'    => 'aot' . $empID . '@example.test',
            'password' => bcrypt('secret123'),
        ]);

        empDetail::create([
            'empID'             => $empID,
            'empDepID'          => $depID,
            'empCompID'         => self::COMP,
            'empStatus'         => '1',
            'empClassification' => 'RGLR',
            'empBasic'          => 26000,
            'empWday'           => 26,
        ]);

        return User::where('empID', $empID)->first();
    }

    private function roleWith(string $name, array $permissions): Role
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        foreach ($permissions as $p) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
        }
        app()['cache']->forget('spatie.permission.cache');

        return $role;
    }

    /** Files OT for $target as $filer, on a date the fixture employees have no schedule for. */
    private function fileFor(User $filer, User $target): array
    {
        Auth::login($filer);

        $date = Carbon::yesterday()->format('Y-m-d');

        $req = Request::create('/admin/overtime/store', 'POST', [
            'employee_id' => $target->empID,
            'dateFrom'    => $date,
            'dateTo'      => $date,
            'timeFrom'    => '20:00',
            'timeTo'      => '22:00',
            'purpose'     => 'Supervisor filing test',
        ]);

        return app(AdminOvertimeController::class)->store($req)->getData(true);
    }

    public function test_supervisor_filing_is_for_approval(): void
    {
        $supervisor = $this->makeEmployee('901', self::DEP_A);
        $target     = $this->makeEmployee('902', self::DEP_A);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $data = $this->fileFor($supervisor, $target);

        $this->assertSame('success', $data['status'], $data['message'] ?? '');

        $ot = Overtime::where('emp_detail_id', $target->empDetail->id)->latest('id')->first();
        $this->assertNotNull($ot, 'an overtime row must be created');
        $this->assertSame(OvertimeStatusEnum::FORAPPROVAL->name, $ot->status);
        $this->assertSame($supervisor->id, (int) $ot->filed_by, 'the filer is recorded for the "Mine only" filter');
    }

    public function test_approver_filing_is_auto_approved(): void
    {
        $hr     = $this->makeEmployee('903', self::DEP_A);
        $target = $this->makeEmployee('904', self::DEP_A);
        $hr->assignRole($this->roleWith('ot-approver-test', ['adminovertime', 'approveovertime']));

        $data = $this->fileFor($hr, $target);

        $this->assertSame('success', $data['status'], $data['message'] ?? '');

        $ot = Overtime::where('emp_detail_id', $target->empDetail->id)->latest('id')->first();
        $this->assertSame(OvertimeStatusEnum::APPROVED->name, $ot->status);
    }

    public function test_supervisor_cannot_file_outside_own_department(): void
    {
        $supervisor = $this->makeEmployee('905', self::DEP_A);
        $outsider   = $this->makeEmployee('906', self::DEP_B);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $data = $this->fileFor($supervisor, $outsider);

        $this->assertSame('error', $data['status']);
        $this->assertStringContainsString('own department', $data['message']);
        $this->assertSame(0, Overtime::where('emp_detail_id', $outsider->empDetail->id)->count());
    }

    public function test_approver_may_file_across_departments(): void
    {
        $hr       = $this->makeEmployee('907', self::DEP_A);
        $outsider = $this->makeEmployee('908', self::DEP_B);
        $hr->assignRole($this->roleWith('ot-approver-test', ['adminovertime', 'approveovertime']));

        $data = $this->fileFor($hr, $outsider);

        $this->assertSame('success', $data['status'], $data['message'] ?? '');
        $this->assertSame(1, Overtime::where('emp_detail_id', $outsider->empDetail->id)->count());
    }
}
