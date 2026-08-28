<?php

namespace App\Providers;

use App\View\Composers\PageTitleComposer;
use GeoIp2\Database\Reader;
use Illuminate\Contracts\Foundation\Application;
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
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Infrastructure\AdminMetricsRepositoryMysql;
use NeoTransposer\Infrastructure\BookRepositoryMysql;
use NeoTransposer\Infrastructure\FeedbackRepositoryMysql;
use NeoTransposer\Infrastructure\GeoIpResolverGeoIp2;
use NeoTransposer\Infrastructure\SongChordRepositoryMysql;
use NeoTransposer\Infrastructure\SongRepositoryMysql;
use NeoTransposer\Infrastructure\UnhappyUserRepositoryMysql;
use NeoTransposer\Infrastructure\UserRepositoryMysql;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookRepository::class, BookRepositoryMysql::class);
        $this->app->bind(SongRepository::class, SongRepositoryMysql::class);
        $this->app->bind(SongChordRepository::class, SongChordRepositoryMysql::class);
        $this->app->bind(UserRepository::class, UserRepositoryMysql::class);
        $this->app->bind(FeedbackRepository::class, FeedbackRepositoryMysql::class);
        $this->app->bind(UnhappyUserRepository::class, UnhappyUserRepositoryMysql::class);
        $this->app->bind(AdminMetricsRepository::class, AdminMetricsRepositoryMysql::class);

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
        View::share('cssFile', config('app.debug')
            ? 'style.css?nocache=' . time()
            : 'compiled-' . config('nt.css_cache') . '.css');

        View::composer('_base', PageTitleComposer::class);

        config(['session.domain' => request()->getHost()]);

        $this->initializeSession();
    }

    private function initializeSession(): void
    {
        if (!session('user')) {
            session(['user' => new User()]);
        }
    }
}
