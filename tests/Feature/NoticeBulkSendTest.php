<?php

namespace Tests\Feature;

use App\Http\Controllers\NoticeController;
use App\Models\department;
use App\Models\Notice;
use App\Services\NoticeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bulk recipients on NoticeController@save (recipient_mode = employees |
 * department | all). Bulk is memo-only and create-only — both enforced
 * server-side — and fans out one notices row per ACTIVE recipient.
 */
class NoticeBulkSendTest extends TestCase
{
    use DatabaseTransactions;

    private function save(array $payload)
    {
        $req = Request::create('/notices/save', 'POST', $payload);
        return app(NoticeController::class)->save($req, app(NoticeService::class));
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'type'  => 'memo',
            'title' => 'Bulk test memo',
            'body'  => 'Bulk send test body.',
        ], $overrides);
    }

    public function test_bulk_disciplinary_is_rejected(): void
    {
        $before = Notice::count();

        $resp = $this->save($this->basePayload([
            'recipient_mode' => 'all',
            'type'           => 'disciplinary',
        ]));

        $data = $resp->getData(true);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('recipient_mode', $data['error']);
        $this->assertSame($before, Notice::count(), 'no notices may be created');
    }

    public function test_bulk_on_edit_is_rejected(): void
    {
        $resp = $this->save($this->basePayload([
            'recipient_mode' => 'all',
            'id'             => 999999,
        ]));

        $data = $resp->getData(true);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('recipient_mode', $data['error']);
    }

    public function test_bogus_employee_ids_are_dropped(): void
    {
        $resp = $this->save($this->basePayload([
            'recipient_mode' => 'employees',
            'employee_ids'   => ['ZZ-NOPE-1', 'ZZ-NOPE-2'],
        ]));

        $data = $resp->getData(true);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('recipient_mode', $data['error']);
    }

    public function test_empty_employee_selection_fails_validation(): void
    {
        $resp = $this->save($this->basePayload([
            'recipient_mode' => 'employees',
            'employee_ids'   => [],
        ]));

        $data = $resp->getData(true);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('employee_ids', $data['error']);
    }

    public function test_department_without_active_employees_is_rejected(): void
    {
        $dept = department::create(['dep_name' => 'ZZ Bulk Test Dept']);

        $resp = $this->save($this->basePayload([
            'recipient_mode' => 'department',
            'department_ids' => [$dept->id],
        ]));

        $data = $resp->getData(true);
        $this->assertSame(201, $data['status']);
        $this->assertArrayHasKey('recipient_mode', $data['error']);
    }

    public function test_employees_mode_fans_out_one_notice_per_active_recipient(): void
    {
        $actives = DB::table('emp_details')->where('empStatus', '1')->limit(3)->pluck('empID');
        if ($actives->isEmpty()) {
            $this->markTestSkipped('No active employees in the database to bulk-send to.');
        }

        $before = Notice::count();
        $resp   = $this->save($this->basePayload([
            'recipient_mode' => 'employees',
            // Bogus ID mixed in must be dropped by the empStatus='1' re-filter.
            'employee_ids'   => $actives->push('ZZ-NOPE-1')->all(),
        ]));

        $data = $resp->getData(true);
        $this->assertSame(200, $data['status']);
        $this->assertSame($actives->count() - 1, $data['recipients']);
        $this->assertSame($before + $data['recipients'], Notice::count());
        $this->assertSame(
            $data['recipients'],
            Notice::where('title', 'Bulk test memo')->where('type', 'memo')->count()
        );
    }

    public function test_all_mode_targets_every_active_employee(): void
    {
        $activeCount = DB::table('emp_details')->where('empStatus', '1')->distinct()->count('empID');
        if ($activeCount === 0) {
            $this->markTestSkipped('No active employees in the database to bulk-send to.');
        }

        $resp = $this->save($this->basePayload(['recipient_mode' => 'all']));

        $data = $resp->getData(true);
        $this->assertSame(200, $data['status']);
        $this->assertSame($activeCount, $data['recipients']);
    }

    public function test_legacy_single_payload_still_creates_one_notice(): void
    {
        $before = Notice::count();

        $resp = $this->save($this->basePayload([
            'employee_id' => 'ZZ-SINGLE-1',   // no recipient_mode → single path
        ]));

        $data = $resp->getData(true);
        $this->assertSame(200, $data['status']);
        $this->assertSame('Notice issued.', $data['msg']);
        $this->assertSame($before + 1, Notice::count());
    }
}
