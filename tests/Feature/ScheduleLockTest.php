<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeScheduleController;
use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * A schedule is locked from edit/delete once the employee has attendance tied to it,
 * so changing/removing it can't diverge or orphan already-recorded punches.
 * The controller methods are invoked directly (no HTTP/auth) against the test DB;
 * DatabaseTransactions rolls everything back.
 */
class ScheduleLockTest extends TestCase
{
    use DatabaseTransactions;

    private string $emp = 'LOCK-EMP-001';

    private function makeSchedule(): EmployeeSchedule
    {
        return EmployeeSchedule::create([
            'employee_id'      => $this->emp,
            'sched_start_date' => '2026-06-16',
            'sched_end_date'   => '2026-06-16',
            'sched_in'         => '08:00:00',
            'sched_out'        => '17:00:00',
            'break_start'      => '12:00:00',
            'break_end'        => '13:00:00',
        ]);
    }

    private function punch(EmployeeSchedule $s): void
    {
        homeAttendance::create([
            'employee_id'     => $this->emp,
            'schedule_id'     => $s->id,
            'attendance_date' => '2026-06-16',
            'time_in'         => '2026-06-16 08:00:00',
            'status'          => 'present',
        ]);
    }

    private function updateRequest(): Request
    {
        return Request::create('/employee-schedules/update', 'PUT', [
            'employee_id'      => $this->emp,
            'sched_start_date' => '2026-06-16',
            'sched_end_date'   => '2026-06-16',
            'sched_in'         => '09:00',
            'sched_out'        => '18:00',
            'break_start'      => '12:00',
            'break_end'        => '13:00',
        ]);
    }

    public function test_delete_blocked_when_attendance_exists(): void
    {
        $s = $this->makeSchedule();
        $this->punch($s);

        $resp = (new EmployeeScheduleController)->destroy($s->id);

        $this->assertSame(409, $resp->getStatusCode());
        $this->assertDatabaseHas('employee_schedules', ['id' => $s->id]); // still there
    }

    public function test_delete_allowed_when_no_attendance(): void
    {
        $s = $this->makeSchedule();

        $resp = (new EmployeeScheduleController)->destroy($s->id);

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertDatabaseMissing('employee_schedules', ['id' => $s->id]);
    }

    public function test_update_blocked_when_attendance_exists(): void
    {
        $s = $this->makeSchedule();
        $this->punch($s);

        $resp = (new EmployeeScheduleController)->update($this->updateRequest(), $s->id);

        $this->assertSame(409, $resp->getStatusCode());
        $s->refresh();
        $this->assertSame('08:00:00', $s->sched_in); // unchanged
    }
}
