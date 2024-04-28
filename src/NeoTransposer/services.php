<?php

namespace NeoTransposer;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;

//Port
$this[Domain\Repository\SongRepository::class] = fn($app) => //Adapter
new Infrastructure\SongRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

$this[Domain\Repository\UserRepository::class] = fn($app) => new Infrastructure\UserRepositoryMysql(
    $app['db'],
    $app[EntityManager::class],
    $app[Domain\Repository\FeedbackRepository::class]
);

$this[Domain\Repository\FeedbackRepository::class] = fn($app) => new Infrastructure\FeedbackRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

//A domain service depending on other domain services
$this[Domain\Service\SongsLister::class] = fn($app) => new Domain\Service\SongsLister(
    $app[Domain\Repository\SongRepository::class],
    $app[Domain\Repository\UserRepository::class],
    $app[Domain\Repository\BookRepository::class]
);

//An application service (use case) depending on a domain service
$this[Application\ListSongsWithUserFeedback::class] = fn($app) => new Application\ListSongsWithUserFeedback(
    $app[Domain\Service\SongsLister::class]
);

//Why factory? One single instance is enough for us
$this[Domain\GeoIp\GeoIpResolver::class] = fn($app) => new Infrastructure\GeoIpResolverGeoIp2(
    new \GeoIp2\Database\Reader($app['root_dir'] . '/' . $app['neoconfig']['mmdb'])
);

$this[Domain\Repository\AdminMetricsRepository::class] = fn($app) => new Infrastructure\AdminMetricsRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

$this[Domain\Service\AdminMetricsReader::class] = fn($app) => new Domain\Service\AdminMetricsReader(
    $app[Domain\Repository\AdminMetricsRepository::class],
    $app[Domain\Repository\BookRepository::class],
    $app[GeoIpResolver::class]
);

$this[Domain\AdminTasks\PopulateUsersCountry::class] = fn($app) => new Domain\AdminTasks\PopulateUsersCountry(
    $app[Domain\Repository\UserRepository::class],
    $app[GeoIpResolver::class]
);

$this[Domain\AdminTasks\CheckSongsRangeConsistency::class] = fn($app) => new Domain\AdminTasks\CheckSongsRangeConsistency(
    $app[Domain\Repository\SongRepository::class]
);

$this[Domain\AdminTasks\CheckUsersRangeConsistency::class] = fn($app) => new Domain\AdminTasks\CheckUsersRangeConsistency(
    $app[Domain\Repository\UserRepository::class]
);

$this[Domain\AdminTasks\RefreshCompiledCss::class]                = fn($app) => new Domain\AdminTasks\RefreshCompiledCss($app);
$this[Domain\AdminTasks\RemoveOldCompiledCss::class]              = fn($app) => new Domain\AdminTasks\RemoveOldCompiledCss($app);
$this[Domain\AdminTasks\CheckChordsOrder::class]                  = fn($app) => new Domain\AdminTasks\CheckChordsOrder($app[Domain\Repository\SongChordRepository::class]);
$this[Domain\AdminTasks\TestAllTranspositions::class]             = fn($app) => new Domain\AdminTasks\TestAllTranspositions($app);
$this[Domain\AdminTasks\GetVoiceRangeOfGoodUsers::class]          = fn($app) => new Domain\AdminTasks\GetVoiceRangeOfGoodUsers($app['db']);
$this[Domain\AdminTasks\CheckOrphanChords::class]                 = fn($app) => new Domain\AdminTasks\CheckOrphanChords($app[Domain\Repository\SongChordRepository::class]);
$this[Domain\AdminTasks\GetPerformanceByNumberOfFeedbacks::class] = fn($app) => new Domain\AdminTasks\GetPerformanceByNumberOfFeedbacks($app['db']);
$this[Domain\AdminTasks\CheckMissingTranslations::class]          = fn($app) => new Domain\AdminTasks\CheckMissingTranslations($app['neoconfig']['languages']);

$this[Domain\Repository\SongChordRepository::class] = fn($app) => new Infrastructure\SongChordRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

$this[Domain\AllSongsReport::class] = fn($app) => new Domain\AllSongsReport(
    $app[Domain\Repository\SongRepository::class],
    $app[Domain\Repository\SongChordRepository::class],
    $app
);

$this[Domain\Repository\BookRepository::class] = fn($app) => new Infrastructure\BookRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

$this[Domain\Repository\UnhappyUserRepository::class] = fn($app) => new Infrastructure\UnhappyUserRepositoryMysql(
    $app['db'],
    $app[EntityManager::class]
);

$this[Domain\Service\FeedbackRecorder::class] = fn($app) => new Domain\Service\FeedbackRecorder(
    $app[Domain\Repository\FeedbackRepository::class],
    $app[Domain\Service\UnhappinessManager::class]
);

//Transitional while UnhappyUser is not hexagonalized
$this[Domain\Service\UnhappinessManager::class] = fn($app) => new Domain\Service\UnhappinessManager(
    $app[Domain\Repository\UnhappyUserRepository::class],
    $app['neoconfig'],
    $app[Domain\Repository\FeedbackRepository::class]
);

$this[Domain\Service\UserWriter::class] = fn($app) => new Domain\Service\UserWriter(
    $app[Domain\Repository\UserRepository::class],
    $app[Domain\Repository\BookRepository::class],
    $app[Domain\Service\UnhappinessManager::class]
);

$this[Domain\AutomaticTransposerFactory::class] = fn($app) => new Domain\AutomaticTransposerFactory(
    $app[Domain\TranspositionFactory::class],
    new Domain\ValueObject\NotesRange($app['neoconfig']['people_range'][0], $app['neoconfig']['people_range'][1]),
    $app[Domain\NotesCalculator::class]
);

//Since this class has no state (all instances are exactly equal), we can cache it in the DC.
$this[Domain\NotesCalculator::class] = fn($app) => new Domain\NotesCalculator();

//Lawrence Krubner was right, the factory pattern is like currying in FP!
$this[Domain\TranspositionFactory::class] = fn($app) => new Domain\TranspositionFactory($app);

$this['factory.ChordPrinter'] = $this->protect(function ($printer) {
    $printer = Domain\ChordPrinter\ChordPrinter::class . $printer;
    return new $printer();
});

$this[IpToLocaleResolver::class] = fn($app) => new IpToLocaleResolver($this[GeoIpResolver::class]);

$this[EntityManager::class] = function ($app) {
    $config = ORMSetup::createAttributeMetadataConfiguration(
        paths: [$app['root_dir'] . "/src"],
        isDevMode: $app['neoconfig']['debug'],
    );

    return new EntityManager($app['db'], $config);
};