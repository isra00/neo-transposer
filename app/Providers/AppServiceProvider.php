<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Infrastructure\BookRepositoryMysql;
use Illuminate\Contracts\Foundation\Application;
use NeoTransposer\Infrastructure\FeedbackRepositoryMysql;
use NeoTransposer\Infrastructure\SongRepositoryMysql;
use NeoTransposer\Infrastructure\UserRepositoryMysql;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BookRepository::class, function (Application $app) {
            return $app->make(BookRepositoryMysql::class);
        });
        $this->app->singleton(SongRepository::class, function (Application $app) {
            return $app->make(SongRepositoryMysql::class);
        });
        $this->app->singleton(UserRepository::class, function (Application $app) {
            return $app->make(UserRepositoryMysql::class);
        });
        $this->app->singleton(FeedbackRepository::class, function (Application $app) {
            return $app->make(FeedbackRepositoryMysql::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
