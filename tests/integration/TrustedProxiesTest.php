<?php

namespace Tests\Integration;

use App\Support\TrustedProxies;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Production chain: visitor -> Cloudflare edge -> host Apache (172.17.0.1) -> container.
 * Before this, $request->ip() was 172.17.0.1 for every visitor (user.register_ip since 2026-05-11).
 */
final class TrustedProxiesTest extends TestCase
{
    private const VISITOR = '203.0.113.7';

    private const CF_EDGE = '172.71.247.192';

    private const DOCKER_GATEWAY = '172.17.0.1';

    private string|false $previousSetting;

    protected function setUp(): void
    {
        // Read in bootstrap/app.php while the app boots, so it must be set before parent::setUp().
        $this->previousSetting = getenv('NT_TRUSTED_PROXIES');
        $this->setTrustedProxies('cloudflare');
        parent::setUp();
        Route::get('/_test/ip', fn (Request $r) => $r->ip());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->setTrustedProxies($this->previousSetting === false ? null : $this->previousSetting);
    }

    private function setTrustedProxies(?string $value): void
    {
        if ($value === null) {
            putenv('NT_TRUSTED_PROXIES');
            unset($_ENV['NT_TRUSTED_PROXIES'], $_SERVER['NT_TRUSTED_PROXIES']);

            return;
        }
        putenv("NT_TRUSTED_PROXIES=$value");
        $_ENV['NT_TRUSTED_PROXIES'] = $_SERVER['NT_TRUSTED_PROXIES'] = $value;
    }

    public function test_the_visitor_ip_is_resolved_through_cloudflare_and_the_host_apache(): void
    {
        $this->ipSeenBy(self::DOCKER_GATEWAY, self::VISITOR . ', ' . self::CF_EDGE)
            ->assertSee(self::VISITOR);
    }

    public function test_a_spoofed_forwarded_for_from_a_client_bypassing_cloudflare_is_ignored(): void
    {
        // Direct to the server: the host Apache appends the attacker's own IP after the fake one.
        $this->ipSeenBy(self::DOCKER_GATEWAY, '1.2.3.4, 198.51.100.9')
            ->assertSee('198.51.100.9');
    }

    public function test_a_spoofed_forwarded_for_before_cloudflare_is_ignored(): void
    {
        // Cloudflare appends the real visitor after whatever the client sent.
        $this->ipSeenBy(self::DOCKER_GATEWAY, '1.2.3.4, ' . self::VISITOR . ', ' . self::CF_EDGE)
            ->assertSee(self::VISITOR);
    }

    public function test_an_untrusted_peer_is_taken_as_the_client(): void
    {
        $this->ipSeenBy('198.51.100.20', self::VISITOR)->assertSee('198.51.100.20');
    }

    public function test_settings(): void
    {
        $this->assertContains('104.16.0.0/13', TrustedProxies::from(null));
        $this->assertSame(TrustedProxies::from(null), TrustedProxies::from('cloudflare'));
        $this->assertSame('*', TrustedProxies::from('*'));
        $this->assertSame(['10.0.0.1', '10.0.0.2'], TrustedProxies::from(' 10.0.0.1, 10.0.0.2 '));
    }

    private function ipSeenBy(string $remoteAddr, string $forwardedFor)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr])
            ->withHeader('X-Forwarded-For', $forwardedFor)
            ->get('/_test/ip');
    }
}
