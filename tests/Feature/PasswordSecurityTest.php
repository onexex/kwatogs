<?php

namespace Tests\Feature;

use App\Http\Controllers\registerCtrl;
use App\Http\Middleware\AuthCheck;
use App\Http\Middleware\CheckEmployeeIp;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\ForcePasswordChange;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Coverage for the two security features:
 *   - login by short username OR email (long-email fix)
 *   - one-time forced password change + complexity rules
 *
 * Runs against the MySQL test DB (.env.testing -> dbdash_test) with
 * DatabaseTransactions so every test rolls back and leaves no rows behind.
 */
class PasswordSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // spatie caches role/permission definitions between calls.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'empID'    => 'TEST-' . substr(md5(uniqid('', true)), 0, 6),
            'email'    => 'user' . uniqid() . '@example.com',
            'username' => null,
            'password' => Hash::make('OldPass1!'),
            'fname'    => 'Juan',
            'lname'    => 'Dela Cruz',
            'role'     => '3',
            'status'   => '1',
            'must_change_password' => false,
        ], $overrides));
    }

    /** Admin role lets login skip the IP allowlist (see loginCtrl). */
    private function makeAdmin(array $overrides = []): User
    {
        $user = $this->makeUser($overrides);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    // ── Login: username OR email ─────────────────────────────────────────────

    public function test_login_succeeds_with_short_username(): void
    {
        $this->makeAdmin(['username' => 'jdelacruz', 'password' => Hash::make('Secret1!')]);

        $res = $this->postJson('/loginSystem', ['username' => 'jdelacruz', 'password' => 'Secret1!']);

        $res->assertOk()->assertJson(['status' => 200]);
        $this->assertNotNull(session('LoggedUserID'));
    }

    public function test_login_succeeds_with_email(): void
    {
        $user = $this->makeAdmin(['username' => 'jdelacruz2', 'password' => Hash::make('Secret1!')]);

        $res = $this->postJson('/loginSystem', ['username' => $user->email, 'password' => 'Secret1!']);

        $res->assertOk()->assertJson(['status' => 200]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = $this->makeAdmin(['password' => Hash::make('Secret1!')]);

        $res = $this->postJson('/loginSystem', ['username' => $user->email, 'password' => 'nope']);

        // 202 = generic invalid-credentials response.
        $res->assertOk()->assertJson(['status' => 202]);
    }

    // ── ForcePasswordChange middleware ───────────────────────────────────────

    private function runMiddleware(Request $request)
    {
        return (new ForcePasswordChange())->handle($request, fn ($r) => response('OK'));
    }

    public function test_flagged_user_is_redirected_to_force_screen(): void
    {
        $this->actingAs($this->makeUser(['must_change_password' => true]));

        $resp = $this->runMiddleware(Request::create('/payroll', 'GET'));

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString('/force-password-change', $resp->headers->get('Location'));
    }

    public function test_flagged_user_ajax_gets_423_with_redirect(): void
    {
        $this->actingAs($this->makeUser(['must_change_password' => true]));

        $request = Request::create('/payroll', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $resp = $this->runMiddleware($request);

        $this->assertEquals(423, $resp->getStatusCode());
        $this->assertStringContainsString('/force-password-change', $resp->getData()->redirect);
    }

    public function test_force_screen_itself_is_exempt(): void
    {
        $this->actingAs($this->makeUser(['must_change_password' => true]));

        $resp = $this->runMiddleware(Request::create('/force-password-change', 'GET'));

        $this->assertEquals('OK', $resp->getContent());
    }

    public function test_unflagged_user_passes_through(): void
    {
        $this->actingAs($this->makeUser(['must_change_password' => false]));

        $resp = $this->runMiddleware(Request::create('/payroll', 'GET'));

        $this->assertEquals('OK', $resp->getContent());
    }

    // ── Forced change endpoint + complexity ──────────────────────────────────

    public function test_force_update_rejects_weak_password(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);
        $this->actingAs($user)
             ->withoutMiddleware([AuthCheck::class, CheckEmployeeIp::class, CheckMaintenanceMode::class]);

        $res = $this->postJson('/force-password-change/update', [
            'current_password'          => 'OldPass1!',
            'new_password'              => 'weak',          // fails complexity
            'new_password_confirmation' => 'weak',
        ]);

        $res->assertStatus(422);
        $this->assertTrue($user->fresh()->must_change_password, 'flag must stay set on failure');
    }

    public function test_force_update_accepts_strong_password_and_clears_flag(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);
        $this->actingAs($user)
             ->withoutMiddleware([AuthCheck::class, CheckEmployeeIp::class, CheckMaintenanceMode::class]);

        $res = $this->postJson('/force-password-change/update', [
            'current_password'          => 'OldPass1!',
            'new_password'              => 'NewStr0ng#Pass',
            'new_password_confirmation' => 'NewStr0ng#Pass',
        ]);

        $res->assertOk()->assertJson(['status' => 200]);

        $fresh = $user->fresh();
        $this->assertFalse($fresh->must_change_password, 'flag should be cleared');
        $this->assertTrue(Hash::check('NewStr0ng#Pass', $fresh->password));
    }

    // ── Username generation (registerCtrl) ───────────────────────────────────

    private function generateUsername(?string $f, ?string $l, string $emp): ?string
    {
        $m = new \ReflectionMethod(registerCtrl::class, 'generateUsername');
        $m->setAccessible(true);
        return $m->invoke(app(registerCtrl::class), $f, $l, $emp);
    }

    private function candidates(?string $f, ?string $l, string $emp): array
    {
        $m = new \ReflectionMethod(registerCtrl::class, 'usernameCandidates');
        $m->setAccessible(true);
        return $m->invoke(app(registerCtrl::class), $f, $l, $emp);
    }

    /** The candidate formats and their order are pure (no DB), so test them directly. */
    public function test_candidate_formats_and_order(): void
    {
        // primary = first initial + surname; secondary = surname initial + first name.
        // (empID passed empty so only the two name formats are returned.)
        $this->assertSame(['jdelacruz', 'djuan'], $this->candidates('Juan', 'Dela Cruz', ''));
        // Accent folding; multi-word surname collapses to one token.
        $this->assertSame(['mdelapena', 'dmaria'], $this->candidates('María', 'Dela Peña', ''));
        // Apostrophe stripped.
        $this->assertSame(['oobrien', 'oowen'], $this->candidates('Owen', "O'Brien", ''));
    }

    public function test_primary_format_used_when_free(): void
    {
        // Synthetic surname so it never clashes with seeded employees.
        $this->assertSame('jzxqvtest', $this->generateUsername('Juan', 'Zxqvtest', 'E1'));
    }

    public function test_collision_swaps_to_surname_initial_plus_firstname(): void
    {
        // Primary "jzxqvtest" taken -> interchange to surname-initial + first name.
        // (Mirrors the real backfill: Realyn/Roselyn Cuenca -> crealyn/croselyn.)
        $this->makeUser(['username' => 'jzxqvtest']);

        $this->assertSame('zjuan', $this->generateUsername('Juan', 'Zxqvtest', 'E1'));
    }

    public function test_falls_back_to_empid_when_both_name_formats_taken(): void
    {
        // Genuinely identical names: both formats already exist -> use the empID.
        $this->makeUser(['username' => 'jzxqvtest']);
        $this->makeUser(['username' => 'zjuan']);

        $this->assertSame('empxyz9', $this->generateUsername('Juan', 'Zxqvtest', 'EMP-XYZ-9'));
    }
}
