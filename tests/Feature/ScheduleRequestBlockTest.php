<?php

namespace Tests\Feature;

use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\ScheduleRequest;
use App\Services\ScheduleRequestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * A Demo Assistant schedule-change request must be refused once the employee has already
 * timed in for that day — applying a new schedule then would diverge the recorded punch.
 */
class ScheduleRequestBlockTest extends TestCase
{
    use DatabaseTransactions;

    private string $emp = 'SRQ-EMP-001';
    private string $date = '2030-06-16'; // future, so the past-date guard doesn't interfere

    private function service(): ScheduleRequestService
    {
        return new ScheduleRequestService();
    }

    public function test_request_blocked_when_already_timed_in(): void
    {
        $sched = EmployeeSchedule::create([
            'employee_id'      => $this->emp,
            'sched_start_date' => $this->date,
            'sched_end_date'   => $this->date,
            'sched_in'         => '08:00:00',
            'sched_out'        => '17:00:00',
            'break_start'      => '12:00:00',
            'break_end'        => '13:00:00',
        ]);
        homeAttendance::create([
            'employee_id'     => $this->emp,
            'schedule_id'     => $sched->id,
            'attendance_date' => $this->date,
            'time_in'         => $this->date . ' 08:00:00',
            'status'          => 'present',
        ]);

        // 09:00–18:00 (9h) with a 1h break = net 8 (valid), but the time-in guard fires first.
        $res = $this->service()->store($this->emp, $this->date, '09:00', '18:00', '12:00', '13:00', 'test');

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('already timed in', $res['message']);
        $this->assertDatabaseMissing('schedule_requests', [
            'employee_id'  => $this->emp,
            'request_date' => $this->date,
        ]);
    }

    public function test_request_allowed_when_no_attendance(): void
    {
        $res = $this->service()->store($this->emp, $this->date, '09:00', '18:00', '12:00', '13:00', 'test');

        $this->assertTrue($res['ok']);
        $this->assertDatabaseHas('schedule_requests', [
            'employee_id'    => $this->emp,
            'request_date'   => $this->date,
            'new_break_start'=> '12:00:00',
            'new_break_end'  => '13:00:00',
        ]);
    }

    public function test_request_rejected_when_not_net_8(): void
    {
        // 09:00–18:00 (9h) with only a 30m break = net 8.5 → must be refused (R2).
        $res = $this->service()->store($this->emp, $this->date, '09:00', '18:00', '12:00', '12:30', 'test');

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('Net working hours', $res['message']);
        $this->assertDatabaseMissing('schedule_requests', [
            'employee_id'  => $this->emp,
            'request_date' => $this->date,
        ]);
    }

    public function test_requested_break_is_applied_to_schedule(): void
    {
        // 06:00–18:00 (12h) with a 4h break = net 8; the applied schedule must carry that break.
        $res = $this->service()->store($this->emp, $this->date, '06:00', '18:00', '11:00', '15:00', 'wide day');

        $this->assertTrue($res['ok']);
        $this->assertDatabaseHas('employee_schedules', [
            'employee_id'      => $this->emp,
            'sched_start_date' => $this->date,
            'break_start'      => '11:00:00',
            'break_end'        => '15:00:00',
        ]);
    }
}
