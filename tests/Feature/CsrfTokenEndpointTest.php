<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The login page calls /csrf-token to self-heal an expired token before retrying sign-in.
 * It must be publicly reachable (pre-auth) and return a usable token.
 */
class CsrfTokenEndpointTest extends TestCase
{
    public function test_csrf_token_endpoint_returns_a_token(): void
    {
        $res = $this->getJson('/csrf-token');

        $res->assertOk()->assertJsonStructure(['token']);
        $this->assertNotEmpty($res->json('token'));
    }
}
