<?php

namespace App\Providers;

use App\View\Composers\PageTitleComposer;
use GeoIp2\Database\Reader;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use NeoTransposer\Domain\AdminTasks\CheckMissingTranslations;
use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\Repository\SongChordRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Repository\UnhappyUserRepository;
use NeoTransposer\Domain\Repository\UniqueSongRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Infrastructure\AdminMetricsRepositoryMysql;
use NeoTransposer\Infrastructure\BookRepositoryMysql;
use NeoTransposer\Infrastructure\FeedbackRepositoryMysql;
use NeoTransposer\Infrastructure\GeoIpResolverGeoIp2;
use NeoTransposer\Infrastructure\SongChordRepositoryMysql;
use NeoTransposer\Infrastructure\SongRepositoryMysql;
use NeoTransposer\Infrastructure\UnhappyUserRepositoryMysql;
use NeoTransposer\Infrastructure\UniqueSongRepositoryMysql;
use NeoTransposer\Infrastructure\UserRepositoryMysql;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Keep throttle counters out of MySQL (Laravel's default cache store is the DB), so a
        // login flood doesn't add DB writes on top of the ones it already causes.
        config(['cache.limiter' => 'file']);

        $this->app->bind(BookRepository::class, BookRepositoryMysql::class);
        $this->app->bind(SongRepository::class, SongRepositoryMysql::class);
        $this->app->bind(SongChordRepository::class, SongChordRepositoryMysql::class);
        $this->app->bind(UserRepository::class, UserRepositoryMysql::class);
        $this->app->bind(FeedbackRepository::class, FeedbackRepositoryMysql::class);
        $this->app->bind(UnhappyUserRepository::class, UnhappyUserRepositoryMysql::class);
        $this->app->bind(AdminMetricsRepository::class, AdminMetricsRepositoryMysql::class);
        $this->app->bind(UniqueSongRepository::class, UniqueSongRepositoryMysql::class);

        $this->app->bind(CheckMissingTranslations::class, function () {
            return new CheckMissingTranslations(config('nt.languages'));
        });

        $this->app->singleton(GeoIpResolver::class, function (Application $app) {
            return $app->make(GeoIpResolverGeoIp2::class, ['reader' => new Reader(base_path() . '/' . config('nt.mmdb'))]);
        });

        $this->app->bind('factory.ChordPrinter', function () {
            return function ($printer) {
                $printer = ChordPrinter::class . $printer;

                return new $printer();
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        View::composer('_base', PageTitleComposer::class);

        $this->configureRateLimiting();

        config(['session.domain' => request()->getHost()]);

        // Cloudflare talks plain HTTP to the origin, so Laravel would generate http:// URLs
        // (redirects, og:url...) that browsers block as mixed content. Force HTTPS if we're in prod.
        if (request()->getHost() === config('nt.canonical_domain')) {
            URL::forceScheme('https');
        }

        $this->initializeSession();
    }

    /**
     * Login and /set-user-data each hit MySQL and create sessions; a single IP looping over
     * them took the server down on 2026-11-21. Limits (config/abuse.php) are generous enough
     * for a group of people logging in together behind one church/home router.
     */
    private function configureRateLimiting(): void
    {
        foreach (config('abuse.rate_limits_per_minute') as $name => $perMinute) {
            RateLimiter::for($name, function (Request $request) use ($name, $perMinute) {
                $ip = self::clientIp($request);

                return match (config('abuse.rate_limit_mode')) {
                    'enforce' => Limit::perMinute($perMinute)->by($ip),
                    'shadow'  => self::shadowLimit($name, $perMinute, $ip, $request),
                    default   => Limit::none(),
                };
            });
        }
    }

    /**
     * Counts like the real limit but never blocks: logs once per IP and minute when the
     * limit would have kicked in, so we can check for false positives before enforcing.
     */
    private static function shadowLimit(string $name, int $perMinute, string $ip, Request $request): Limit
    {
        $hits = RateLimiter::hit("shadow:$name:" . sha1($ip), 60);

        if ($hits === $perMinute + 1) {
            Log::warning('Rate limit would block', [
                'limiter'    => $name,
                'ip'         => $ip,
                'per_minute' => $perMinute,
                'path'       => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return Limit::none();
    }

    /**
     * Behind Cloudflare + the host Apache proxy, the real visitor IP is in CF-Connecting-IP.
     */
    private static function clientIp(Request $request): string
    {
        return $request->header('CF-Connecting-IP') ?: (string) $request->ip();
    }

    private function initializeSession(): void
    {
        if (!session('user')) {
            session(['user' => new User()]);
        }
    }
}
