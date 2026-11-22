<?php

namespace Tests\Integration;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * A single IP looping over login and /set-user-data took production down on 2026-11-21.
 * Both endpoints are throttled per visitor IP ($request->ip(), see TrustedProxiesTest), with
 * counters kept out of MySQL, and config('abuse.rate_limit_mode') switches between
 * off / shadow (log only) / enforce.
 */
final class RateLimitTest extends TestCase
{
    use DatabaseTransactions;

    private string $ip;

    protected function setUp(): void
    {
        parent::setUp();
        // Counters live in the file cache, which outlives the DB transaction: use a fresh IP per test.
        $this->ip = self::randomIp();
    }

    private static function randomIp(): string
    {
        // Documentation range (2001:db8::/32): never a trusted proxy, unique enough per test.
        return sprintf('2001:db8:%x:%x::%x', random_int(0, 0xFFFF), random_int(0, 0xFFFF), random_int(1, 0xFFFF));
    }

    public function test_rate_limiter_does_not_use_the_database_cache(): void
    {
        $limiter = $this->app->make(RateLimiter::class);
        $cache = (new \ReflectionProperty(RateLimiter::class, 'cache'))->getValue($limiter);

        $this->assertInstanceOf(FileStore::class, $cache->getStore());
    }

    public function test_shadow_is_the_default_mode(): void
    {
        $this->assertSame('shadow', config('abuse.rate_limit_mode'));
    }

    public function test_enforce_throttles_set_user_data_per_client_ip(): void
    {
        config(['abuse.rate_limit_mode' => 'enforce']);
        $this->loginAsTestUser();

        for ($i = 1; $i <= 30; $i++) {
            $this->postSetUserData($this->ip)->assertStatus(302);
        }

        $this->postSetUserData($this->ip)->assertStatus(429);

        // Another visitor is unaffected.
        $this->postSetUserData(self::randomIp())->assertStatus(302);
    }

    public function test_a_spoofed_cf_connecting_ip_does_not_escape_the_limit(): void
    {
        config(['abuse.rate_limit_mode' => 'enforce']);

        // A client hitting the server directly, rotating CF-Connecting-IP on every request.
        for ($i = 1; $i <= 20; $i++) {
            $this->postLogin($this->ip, "rate-limit-$i@example.com", self::randomIp())->assertStatus(302);
        }

        $this->postLogin($this->ip, 'rate-limit-21@example.com', self::randomIp())->assertStatus(429);
    }

    public function test_enforce_throttles_login_per_client_ip(): void
    {
        config(['abuse.rate_limit_mode' => 'enforce']);

        for ($i = 1; $i <= 20; $i++) {
            $this->postLogin($this->ip, "rate-limit-$i@example.com")->assertStatus(302);
        }

        $this->postLogin($this->ip, 'rate-limit-21@example.com')->assertStatus(429);
    }

    public function test_enforce_throttles_feedback_per_client_ip(): void
    {
        config(['abuse.rate_limit_mode' => 'enforce']);

        // Unauthenticated AJAX feedback answers 408 from the controller: enough to see it pass the throttle.
        for ($i = 1; $i <= 10; $i++) {
            $this->postFeedback($this->ip)->assertStatus(408);
        }

        $this->postFeedback($this->ip)->assertStatus(429);
    }

    public function test_shadow_never_blocks_and_logs_once_per_minute(): void
    {
        config(['abuse.rate_limit_mode' => 'shadow']);
        Log::spy();

        for ($i = 1; $i <= 25; $i++) {
            $this->postLogin($this->ip, "rate-limit-$i@example.com")->assertStatus(302);
        }

        Log::shouldHaveReceived('warning')
            ->with('Rate limit would block', \Mockery::on(fn ($ctx) => $ctx['ip'] === $this->ip && $ctx['limiter'] === 'login'))
            ->once();
    }

    public function test_off_never_blocks_nor_logs(): void
    {
        config(['abuse.rate_limit_mode' => 'off']);
        Log::spy();

        for ($i = 1; $i <= 25; $i++) {
            $this->postLogin($this->ip, "rate-limit-$i@example.com")->assertStatus(302);
        }

        Log::shouldNotHaveReceived('warning');
    }

    private function loginAsTestUser(): void
    {
        $idUser = DB::table('user')->insertGetId([
            'email'        => 'rate-limit-test@example.com',
            'lowest_note'  => 'A1',
            'highest_note' => 'E3',
            'id_book'      => 2,
        ]);
        $this->withSession(['user' => new User('rate-limit-test@example.com', $idUser, new NotesRange('A1', 'E3'))]);
    }

    private function postSetUserData(string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/set-user-data', ['book' => 2]);
    }

    private function postFeedback(string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/feedback', ['id_song' => 1, 'worked' => 1]);
    }

    private function postLogin(string $ip, string $email, ?string $spoofedCfIp = null)
    {
        $request = $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        if ($spoofedCfIp !== null) {
            $request = $request->withHeader('CF-Connecting-IP', $spoofedCfIp);
        }

        return $request->post('/en/login', ['email' => $email]);
    }
}
