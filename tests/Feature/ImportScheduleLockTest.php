<?php

namespace Tests\Feature;

use App\Models\homeAttendance;
use App\Services\AttendanceImportService;
use App\Services\ScheduleImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The schedule/attendance imports must refuse a day the employee already has attendance
 * for — same lock as the Scheduler UI. Guards are exercised directly (via reflection on
 * the private bulk-check methods) so no User rows / template column layout are needed.
 */
class ImportScheduleLockTest extends TestCase
{
    use DatabaseTransactions;

    private string $emp = 'IMP-EMP-001';
    private string $date = '2030-06-16';

    private function sealPunch(): void
    {
        homeAttendance::create([
            'employee_id'     => $this->emp,
            'schedule_id'     => 1, // no FK constraint on this column
            'attendance_date' => $this->date,
            'time_in'         => $this->date . ' 08:00:00',
            'status'          => 'present',
        ]);
    }

    /** @return array{0:array,1:array} [$prepared, $result] after the guard ran */
    private function runGuard(object $svc, string $method, array $prepared): array
    {
        $result = ['errors' => []];
        $m = new \ReflectionMethod($svc, $method);
        $m->setAccessible(true);
        $args = [&$prepared, &$result];
        $m->invokeArgs($svc, $args);
        return [$prepared, $result];
    }

    public function test_schedule_import_rejects_attended_day(): void
    {
        $this->sealPunch();
        $prepared = [[
            'employee_id' => $this->emp, 'sched_start_date' => $this->date,
            'sched_in' => '08:00', 'sched_out' => '17:00', '_line' => 2,
        ]];

        [$kept, $result] = $this->runGuard(new ScheduleImportService(), 'flagAttended', $prepared);

        $this->assertCount(0, $kept, 'row dropped');
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('already has attendance', $result['errors'][0]);
    }

    public function test_schedule_import_allows_free_day(): void
    {
        $prepared = [[
            'employee_id' => $this->emp, 'sched_start_date' => $this->date,
            'sched_in' => '08:00', 'sched_out' => '17:00', '_line' => 2,
        ]];

        [$kept, $result] = $this->runGuard(new ScheduleImportService(), 'flagAttended', $prepared);

        $this->assertCount(1, $kept);
        $this->assertEmpty($result['errors']);
    }

    public function test_attendance_import_rejects_day_with_open_punch(): void
    {
        $this->sealPunch(); // punch, no summary
        $prepared = [[
            'employee_id' => $this->emp, 'date' => $this->date,
            '_key' => $this->emp . '|' . $this->date, '_line' => 2,
        ]];

        [$kept, $result] = $this->runGuard(new AttendanceImportService(), 'flagExisting', $prepared);

        $this->assertCount(0, $kept, 'row dropped');
        $this->assertNotEmpty($result['errors']);
    }
}
