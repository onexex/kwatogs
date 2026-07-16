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
 * AdminOvertimeController@update lets a filing be corrected while it is still
 * FOR APPROVAL. Once an approver has acted the figures may already have fed
 * payroll, so the record is frozen. A supervisor may only correct their own
 * filing and only within their own department; a manager may correct any pending
 * one. The full filing gauntlet re-runs, excluding the record from its own
 * overlap check.
 */
class AdminOvertimeEditTest extends TestCase
{
    use DatabaseTransactions;

    private const DEP_A = 16;
    private const DEP_B = 14;
    private const COMP  = '01';

    private function makeEmployee(string $empID, int $depID): User
    {
        User::create([
            'empID'    => $empID,
            'fname'    => 'Test',
            'lname'    => 'Employee' . $empID,
            'email'    => 'aote' . $empID . '@example.test',
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

    private function fileFor(User $filer, User $target): Overtime
    {
        Auth::login($filer);
        $date = Carbon::yesterday()->format('Y-m-d');

        $req = Request::create('/admin/overtime/store', 'POST', [
            'employee_id' => $target->empID,
            'dateFrom'    => $date,
            'dateTo'      => $date,
            'timeFrom'    => '20:00',
            'timeTo'      => '22:00',
            'purpose'     => 'Original purpose',
        ]);

        $data = app(AdminOvertimeController::class)->store($req)->getData(true);
        $this->assertSame('success', $data['status'], $data['message'] ?? '');

        return Overtime::where('emp_detail_id', $target->empDetail->id)->latest('id')->first();
    }

    /** Edits $ot as $editor, moving the range to 20:00–23:00 (deliberately overlapping the original). */
    private function editAs(User $editor, Overtime $ot, User $target, array $overrides = []): array
    {
        Auth::login($editor);
        $date = Carbon::yesterday()->format('Y-m-d');

        $req = Request::create('/admin/overtime/' . $ot->id . '/update', 'POST', array_merge([
            'employee_id' => $target->empID,
            'dateFrom'    => $date,
            'dateTo'      => $date,
            'timeFrom'    => '20:00',
            'timeTo'      => '23:00',
            'purpose'     => 'Corrected purpose',
        ], $overrides));

        return app(AdminOvertimeController::class)->update($req, $ot)->getData(true);
    }

    public function test_supervisor_can_edit_own_for_approval_filing(): void
    {
        $supervisor = $this->makeEmployee('911', self::DEP_A);
        $target     = $this->makeEmployee('912', self::DEP_A);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $ot   = $this->fileFor($supervisor, $target);
        $data = $this->editAs($supervisor, $ot, $target);

        $this->assertSame('success', $data['status'], $data['message'] ?? '');

        $ot->refresh();
        $this->assertSame('Corrected purpose', $ot->purpose);
        $this->assertSame('23:00', Carbon::parse($ot->time_out)->format('H:i'));
        $this->assertEquals(3.0, (float) $ot->total_hrs, 'hours must be recomputed from the new range');
    }

    public function test_edit_stays_for_approval_under_the_original_filer(): void
    {
        $supervisor = $this->makeEmployee('913', self::DEP_A);
        $target     = $this->makeEmployee('914', self::DEP_A);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $ot = $this->fileFor($supervisor, $target);
        $this->editAs($supervisor, $ot, $target);

        $ot->refresh();
        $this->assertSame(OvertimeStatusEnum::FORAPPROVAL->name, $ot->status, 'an edit is a correction, not a re-filing');
        $this->assertSame($supervisor->id, (int) $ot->filed_by);
    }

    public function test_approved_filing_cannot_be_edited(): void
    {
        $hr     = $this->makeEmployee('915', self::DEP_A);
        $target = $this->makeEmployee('916', self::DEP_A);
        $hr->assignRole($this->roleWith('ot-approver-test', ['adminovertime', 'approveovertime']));

        // A manager files pre-approved, so this row is frozen on arrival.
        $ot   = $this->fileFor($hr, $target);
        $this->assertSame(OvertimeStatusEnum::APPROVED->name, $ot->status);

        $data = $this->editAs($hr, $ot, $target);

        $this->assertSame('error', $data['status']);
        $this->assertStringContainsString('For Approval', $data['message']);

        $ot->refresh();
        $this->assertSame('Original purpose', $ot->purpose, 'the frozen row must be untouched');
    }

    public function test_supervisor_cannot_edit_someone_elses_filing(): void
    {
        $filer  = $this->makeEmployee('917', self::DEP_A);
        $other  = $this->makeEmployee('918', self::DEP_A);
        $target = $this->makeEmployee('919', self::DEP_A);
        $filer->assignRole($this->roleWith('supervisor', ['adminovertime']));
        $other->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $ot   = $this->fileFor($filer, $target);
        $data = $this->editAs($other, $ot, $target);

        $this->assertSame('error', $data['status']);
        $this->assertStringContainsString('you filed', $data['message']);

        $ot->refresh();
        $this->assertSame('Original purpose', $ot->purpose);
    }

    public function test_manager_can_edit_a_supervisors_pending_filing(): void
    {
        $supervisor = $this->makeEmployee('920', self::DEP_A);
        $hr         = $this->makeEmployee('921', self::DEP_B);
        $target     = $this->makeEmployee('922', self::DEP_A);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));
        $hr->assignRole($this->roleWith('ot-approver-test', ['adminovertime', 'approveovertime']));

        $ot   = $this->fileFor($supervisor, $target);
        $data = $this->editAs($hr, $ot, $target);

        $this->assertSame('success', $data['status'], $data['message'] ?? '');

        $ot->refresh();
        $this->assertSame('Corrected purpose', $ot->purpose);
        $this->assertSame($supervisor->id, (int) $ot->filed_by, 'a manager editing must not steal the filer');
    }

    public function test_supervisor_cannot_edit_a_filing_outside_own_department(): void
    {
        $hr         = $this->makeEmployee('923', self::DEP_B);
        $supervisor = $this->makeEmployee('924', self::DEP_A);
        $outsider   = $this->makeEmployee('925', self::DEP_B);
        $hr->assignRole($this->roleWith('ot-approver-test', ['adminovertime', 'approveovertime']));
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        // An out-of-department pending row: filed by HR for a DEP_B employee.
        $ot = $this->fileFor($hr, $outsider);
        $ot->forceFill(['status' => OvertimeStatusEnum::FORAPPROVAL->name])->save();

        $data = $this->editAs($supervisor, $ot, $outsider);

        $this->assertSame('error', $data['status']);

        $ot->refresh();
        $this->assertSame('Original purpose', $ot->purpose);
    }

    public function test_edit_still_rejects_a_clash_with_a_different_record(): void
    {
        $supervisor = $this->makeEmployee('926', self::DEP_A);
        $target     = $this->makeEmployee('927', self::DEP_A);
        $supervisor->assignRole($this->roleWith('supervisor', ['adminovertime']));

        $date = Carbon::yesterday()->format('Y-m-d');
        $ot   = $this->fileFor($supervisor, $target);

        // A second, separate pending filing later the same night.
        Overtime::create([
            'emp_detail_id' => $target->empDetail->id,
            'filed_by'      => $supervisor->id,
            'status'        => OvertimeStatusEnum::FORAPPROVAL->name,
            'date_from'     => $date,
            'date_to'       => $date,
            'time_in'       => '23:00',
            'time_out'      => '23:59',
            'purpose'       => 'Second filing',
            'total_hrs'     => 0.98,
            'total_pay'     => 100,
            'day_type'      => 'rest_day',
        ]);

        // Editing the first to run until 23:30 now collides with the second.
        $data = $this->editAs($supervisor, $ot, $target, ['timeTo' => '23:30']);

        $this->assertSame('error', $data['status']);
        $this->assertStringContainsString('overlapping', $data['message']);
    }
}
