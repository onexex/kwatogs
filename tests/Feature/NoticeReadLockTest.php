<?php

namespace Tests\Feature;

use App\Http\Controllers\NoticeController;
use App\Models\Notice;
use App\Services\NoticeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * A notice the employee has already read (is_read = true) is frozen: HR can
 * neither edit nor delete it. Unread notices stay fully editable/deletable.
 */
class NoticeReadLockTest extends TestCase
{
    use DatabaseTransactions;

    private function makeNotice(bool $read): Notice
    {
        return Notice::create([
            'employee_id' => 'ZZ-LOCK-1',
            'type'        => 'memo',
            'title'       => 'Original title',
            'body'        => 'Original body.',
            'issued_by'   => 'HR',
            'issued_at'   => now()->toDateString(),
            'status'      => 'active',
            'is_read'     => $read,
            'read_at'     => $read ? now() : null,
        ]);
    }

    private function editNotice(int $id): array
    {
        $req = Request::create('/notices/save', 'POST', [
            'id'             => $id,
            'recipient_mode' => 'single',
            'employee_id'    => 'ZZ-LOCK-1',
            'type'           => 'memo',
            'title'          => 'Edited title',
            'body'           => 'Edited body.',
        ]);
        return app(NoticeController::class)->save($req, app(NoticeService::class))->getData(true);
    }

    private function deleteNotice(int $id): array
    {
        $req = Request::create('/notices/delete', 'POST', ['id' => $id]);
        return app(NoticeController::class)->delete($req)->getData(true);
    }

    public function test_read_notice_cannot_be_edited(): void
    {
        $notice = $this->makeNotice(true);

        $data = $this->editNotice($notice->id);

        $this->assertSame(202, $data['status']);
        $this->assertSame('Original title', $notice->fresh()->title, 'edit must not apply');
    }

    public function test_read_notice_cannot_be_deleted(): void
    {
        $notice = $this->makeNotice(true);

        $data = $this->deleteNotice($notice->id);

        $this->assertSame(202, $data['status']);
        $this->assertNotNull(Notice::find($notice->id), 'notice must survive');
    }

    public function test_unread_notice_can_be_edited(): void
    {
        $notice = $this->makeNotice(false);

        $data = $this->editNotice($notice->id);

        $this->assertSame(200, $data['status']);
        $this->assertSame('Edited title', $notice->fresh()->title);
    }

    public function test_unread_notice_can_be_deleted(): void
    {
        $notice = $this->makeNotice(false);

        $data = $this->deleteNotice($notice->id);

        $this->assertSame(200, $data['status']);
        $this->assertNull(Notice::find($notice->id));
    }
}
