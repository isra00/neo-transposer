<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Testing\TestResponse;

/**
 * The admin pages render user data and CSRF tokens, so they must not be storable by
 * any cache. Laravel's default `no-cache, private` still permits the browser to write
 * the response to disk; only `no-store` forbids it.
 *
 * Note these run through Laravel, not Apache, so they cannot see the mod_expires
 * interaction described in PreventResponseCaching — they pin the headers the app emits.
 */
final class AdminCacheHeadersTest extends TestCase
{
    private const PASSWORD = 'correct-horse-battery-staple';

    protected function setUp(): void
    {
        parent::setUp();

        config(['nt.admins' => ['tester' => ['ROLE_ADMIN', password_hash(self::PASSWORD, PASSWORD_DEFAULT)]]]);
    }

    private function getAsAdmin(string $uri): TestResponse
    {
        // Set directly rather than via an Authorization header so the test does not depend
        // on the server's Basic-auth header parsing, and never touches the debug bypass.
        return $this->withServerVariables([
            'PHP_AUTH_USER' => 'tester',
            'PHP_AUTH_PW' => self::PASSWORD,
        ])->get($uri);
    }

    private function assertNotStorable(TestResponse $response, string $context): void
    {
        $cacheControl = $response->headers->get('Cache-Control') ?? '';

        $this->assertStringContainsString('no-store', $cacheControl, "{$context} must not be storable");
        $this->assertStringContainsString('private', $cacheControl, "{$context} must not be cached by proxies");
    }

    public function test_admin_pages_are_not_storable_by_any_cache(): void
    {
        $response = $this->getAsAdmin('/admin/insert-song');

        $response->assertOk();
        $this->assertNotStorable($response, 'the insert-song form');
    }

    public function test_the_basic_auth_challenge_is_not_storable(): void
    {
        $response = $this->get('/admin/insert-song');

        $response->assertStatus(401);
        $this->assertNotStorable($response, 'the 401 challenge');
    }

    public function test_admin_pages_send_a_past_expires_so_mod_expires_cannot_override_them(): void
    {
        $expires = $this->getAsAdmin('/admin/insert-song')->headers->get('Expires');

        $this->assertNotNull($expires, 'a past Expires is what stops mod_expires adding a max-age');
        $this->assertLessThan(
            time(),
            strtotime($expires),
            "Expires must be in the past, got '{$expires}'"
        );
    }

    public function test_public_pages_are_left_cacheable(): void
    {
        // Guards against the middleware being promoted to the web group by accident:
        // no-store on every page would defeat the browser's back/forward cache sitewide.
        $cacheControl = $this->get('/es/login')->headers->get('Cache-Control') ?? '';

        $this->assertStringNotContainsString('no-store', $cacheControl);
    }
}
