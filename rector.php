<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    $rectorConfig->sets([
//        LevelSetList::UP_TO_PHP_81,
//        SetList::PHP_81,
//        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
//        SetList::CODE_QUALITY,
//        SetList::CODING_STYLE, /* not processed */
//        SetList::DEAD_CODE,
//        SetList::NAMING,
        SetList::PRIVATIZATION,
//        SetList::TYPE_DECLARATION,
//        SetList::TYPE_DECLARATION_STRICT,
//        SetList::EARLY_RETURN,
    ]);

    $rectorConfig->skip([
        ChangeReadOnlyVariableWithDefaultValueToConstantRector::class,

        //Need to be mocked, but PHPUnit does not support mocking final classes.
        FinalizeClassesWithoutChildrenRector::class => [
            __DIR__ . '/src/NeoTransposer/Domain/Entity/User.php',
            __DIR__ . '/src/NeoTransposer/Domain/Entity/Song.php',
            __DIR__ . '/src/NeoTransposer/Domain/ValueObject/Chord.php',
            __DIR__ . '/src/NeoTransposer/Domain/AutomaticTransposerFactory.php',
            __DIR__ . '/src/NeoTransposer/Domain/AutomaticTransposer.php',
            __DIR__ . '/src/NeoTransposer/Domain/PeopleCompatibleCalculation.php',
            __DIR__ . '/src/NeoTransposer/Domain/NotesCalculator.php',
            __DIR__ . '/src/NeoTransposer/Domain/Transposition.php',
        ]
    ]);
};
