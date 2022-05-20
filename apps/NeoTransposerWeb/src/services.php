<?php

namespace NeoTransposerApp;

use NeoTransposerApp\Domain\GeoIp\GeoIpResolver;
use NeoTransposerApp\Domain\GeoIp\IpToLocaleResolver;
use NeoTransposerApp\Domain;

//Port
$this[Domain\Repository\SongRepository::class] = function ($app) {
    //Adapter
    return new Infrastructure\SongRepositoryMysql($app['db']);
};

$this[Domain\Repository\UserRepository::class] = function ($app) {
    return new Infrastructure\UserRepositoryMysql(
        $app['db'],
        $app[Domain\Repository\FeedbackRepository::class]
    );
};

$this[Domain\Repository\FeedbackRepository::class] = function ($app) {
    return new Infrastructure\FeedbackRepositoryMysql($app['db']);
};

//A domain service depending on other domain services
$this[Domain\Service\SongsLister::class] = function ($app) {
    return new Domain\Service\SongsLister(
        $app[Domain\Repository\SongRepository::class],
        $app[Domain\Repository\UserRepository::class],
        $app[Domain\Repository\BookRepository::class]
    );
};

//An application service (use case) depending on a domain service
$this[Application\ListSongsWithUserFeedback::class] = function ($app) {
    return new Application\ListSongsWithUserFeedback(
        $app[Domain\Service\SongsLister::class]
    );
};

//Factory bc one single instance is enough for us
$this[Domain\GeoIp\GeoIpResolver::class] = function ($app) {
    return new Infrastructure\GeoIpResolverGeoIp2(
        new \GeoIp2\Database\Reader($app['neoconfig']['mmdb'])
    );
};

$this[Domain\Repository\AdminMetricsRepository::class] = function ($app) {
    return new Infrastructure\AdminMetricsRepositoryMysql($app['db']);
};

$this[Application\ReadAdminMetrics::class] = function ($app) {
    return new Application\ReadAdminMetrics(
        new Domain\Service\AdminMetricsReader(
            $app[Domain\Repository\AdminMetricsRepository::class],
            $app[Domain\Repository\BookRepository::class],
            $app[GeoIpResolver::class]
        )
    );
};

$this[Domain\AdminTasks\PopulateUsersCountry::class] = function ($app) {
    return new Domain\AdminTasks\PopulateUsersCountry(
        $app[Domain\Repository\UserRepository::class],
        $app[GeoIpResolver::class]
    );
};

$this[Domain\AdminTasks\CheckSongsRangeConsistency::class] = function ($app) {
    return new Domain\AdminTasks\CheckSongsRangeConsistency($app[Domain\Repository\SongRepository::class]);
};

$this[Domain\AdminTasks\CheckUsersRangeConsistency::class] = function ($app) {
    return new Domain\AdminTasks\CheckUsersRangeConsistency($app[Domain\Repository\UserRepository::class]);
};

$this[Domain\AdminTasks\RefreshCompiledCss::class] = function ($app) {
    return new Domain\AdminTasks\RefreshCompiledCss($app);
};

$this[Domain\AdminTasks\RemoveOldCompiledCss::class] = function ($app) {
    return new Domain\AdminTasks\RemoveOldCompiledCss($app);
};

$this[Domain\AdminTasks\CheckChordsOrder::class] = function ($app) {
    return new Domain\AdminTasks\CheckChordsOrder($app[Domain\Repository\SongChordRepository::class]);
};

$this[Domain\AdminTasks\TestAllTranspositions::class] = function ($app) {
    return new Domain\AdminTasks\TestAllTranspositions(
        $app,
        $app['neoconfig']['test_all_transpositions_expected'],
        $app['neoconfig']['test_all_transpositions_expected_pc']
    );
};

$this[Domain\AdminTasks\GetVoiceRangeOfGoodUsers::class] = function ($app) {
    return new Domain\AdminTasks\GetVoiceRangeOfGoodUsers($app['db']);
};

$this[Domain\AdminTasks\CheckOrphanChords::class] = function ($app) {
    return new Domain\AdminTasks\CheckOrphanChords($app[Domain\Repository\SongChordRepository::class]);
};

$this[Domain\AdminTasks\GetPerformanceByNumberOfFeedbacks::class] = function ($app) {
    return new Domain\AdminTasks\GetPerformanceByNumberOfFeedbacks($app['db']);
};

$this[Domain\AdminTasks\CheckMissingTranslations::class] = function ($app) {
    return new Domain\AdminTasks\CheckMissingTranslations($app['neoconfig']['languages']);
};

$this[Domain\Repository\SongChordRepository::class] = function ($app) {
    return new Infrastructure\SongChordRepositoryMysql($app['db']);
};

$this[Domain\Service\AllSongsReporter::class] = function ($app) {
    return new Domain\Service\AllSongsReporter(
        $app[Domain\Repository\SongRepository::class],
        $app[Domain\Repository\SongChordRepository::class],
        $app
    );
};

$this[Domain\Repository\BookRepository::class] = function ($app) {
    return new Infrastructure\BookRepositoryMysql($app['db']);
};

$this[Domain\Repository\UnhappyUserRepository::class] = function ($app) {
    return new Infrastructure\UnhappyUserRepositoryMysql($app['db']);
};

$this[Domain\Service\FeedbackRecorder::class] = function ($app) {
    return new Domain\Service\FeedbackRecorder(
        $app[Domain\Repository\FeedbackRepository::class],
        $app[Domain\Service\UnhappinessManager::class]
    );
};

//Transitional while UnhappyUser is not hexagonalized
$this[Domain\Service\UnhappinessManager::class] = function ($app) {
    return new Domain\Service\UnhappinessManager(
        $app[Domain\Repository\UnhappyUserRepository::class],
        $app['neoconfig'],
        $app[Domain\Repository\FeedbackRepository::class]
    );
};

$this[Domain\Service\UserWriter::class] = function ($app) {
    return new Domain\Service\UserWriter(
        $app[Domain\Repository\UserRepository::class],
        $app[Domain\Repository\BookRepository::class],
        $app[Domain\Service\UnhappinessManager::class]
    );
};

$this[Domain\AutomaticTransposerFactory::class] = function ($app) {
    return new Domain\AutomaticTransposerFactory(
        $app[Domain\TranspositionFactory::class],
        new Domain\ValueObject\NotesRange($app['neoconfig']['people_range'][0], $app['neoconfig']['people_range'][1]),
        $app[Domain\NotesCalculator::class]
    );
};

//Since this class has no state (all instances are exactly equal), we can cache it in the DC.
$this[Domain\NotesCalculator::class] = function () {
    return new Domain\NotesCalculator();
};

//Lawrence Krubner was right, the factory pattern is like currying in FP!
$this[Domain\TranspositionFactory::class] = function ($app) {
    return new Domain\TranspositionFactory($app);
};

$this['factory.ChordPrinter'] = $this->protect(function ($printer) {
    $printer = Domain\ChordPrinter\ChordPrinter::class . $printer;
    return new $printer();
});

$this[IpToLocaleResolver::class] = function () {
    return new IpToLocaleResolver($this[GeoIpResolver::class]);
};
