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
 * Both endpoints are throttled per visitor IP (CF-Connecting-IP), with counters kept out of
 * MySQL, and config('abuse.rate_limit_mode') switches between off / shadow (log only) / enforce.
 */
final class RateLimitTest extends TestCase
{
    use DatabaseTransactions;

    private string $ip;

    protected function setUp(): void
    {
        parent::setUp();
        // Counters live in the file cache, which outlives the DB transaction: use a fresh IP per test.
        $this->ip = '198.51.100.' . random_int(1, 254) . '-' . uniqid();
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
        $this->postSetUserData($this->ip . '-other')->assertStatus(302);
    }

    public function test_enforce_throttles_login_per_client_ip(): void
    {
        config(['abuse.rate_limit_mode' => 'enforce']);

        for ($i = 1; $i <= 20; $i++) {
            $this->postLogin($this->ip, "rate-limit-$i@example.com")->assertStatus(302);
        }

        $this->postLogin($this->ip, 'rate-limit-21@example.com')->assertStatus(429);
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
        return $this->withHeader('CF-Connecting-IP', $ip)->post('/set-user-data', ['book' => 2]);
    }

    private function postLogin(string $ip, string $email)
    {
        return $this->withHeader('CF-Connecting-IP', $ip)->post('/en/login', ['email' => $email]);
    }
}
