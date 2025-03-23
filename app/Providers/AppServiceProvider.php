<?php

namespace App\Providers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Repository\UnhappyUserRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Infrastructure\BookRepositoryMysql;
use Illuminate\Contracts\Foundation\Application;
use NeoTransposer\Infrastructure\FeedbackRepositoryMysql;
use NeoTransposer\Infrastructure\GeoIpResolverGeoIp2;
use NeoTransposer\Infrastructure\MysqlRepository;
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
        $this->app->bind(UserRepository::class, UserRepositoryMysql::class);
        $this->app->bind(FeedbackRepository::class, FeedbackRepositoryMysql::class);
        $this->app->bind(UnhappyUserRepository::class, UnhappyUserRepositoryMysql::class);

        $this->app->singleton(GeoIpResolver::class, function (Application $app) {
            return $app->make(GeoIpResolverGeoIp2::class, ['reader' => new \GeoIp2\Database\Reader(base_path() . '/' . config('nt.mmdb'))]);
        });

        /** @todo Migrar todo a Illuminate y dejar de usar Doctrine */
        $this->app->singleton(EntityManager::class, function (Application $app) {
            return new EntityManager(MysqlRepository::dbal(), ORMSetup::createAttributeMetadataConfiguration(
                paths: [base_path() . "/src"],
                isDevMode: config('app.debug')
            ));
        });

        $this->app->bind('factory.ChordPrinter', function () {
            return function($printer) {
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
        View::share('cssVersion', config('app.debug')
            ? time()
            : trim(exec('git log --pretty="%h" -n1 HEAD')));

        // Poner aquí la chicha de NeoApp.php
        $this->initializeSession();
    }

    private function initializeSession(): void
    {
        if (!session('user')) {
            session(['user' => new User()]);
        }
    }
}
