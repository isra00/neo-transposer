<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryAndToEarlyReturnRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src/NeoTransposer/Domain',
        __DIR__ . '/tests'
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::PHP_81,
        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
//        SetList::CODING_STYLE,
//        SetList::TYPE_DECLARATION,
//        SetList::TYPE_DECLARATION_STRICT,
    ]);

    $rectorConfig->skip([
        ChangeReadOnlyVariableWithDefaultValueToConstantRector::class,
        ChangeOrIfReturnToEarlyReturnRector::class,
        ChangeOrIfContinueToMultiContinueRector::class,
        ReturnBinaryAndToEarlyReturnRector::class,
        RenameParamToMatchTypeRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RenameVariableToMatchNewTypeRector::class,
        RenamePropertyToMatchTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        ExplicitBoolCompareRector::class,
        JsonThrowOnErrorRector::class,
        UseIdenticalOverEqualWithSameTypeRector::class,
        OptionalParametersAfterRequiredRector::class,

        AddDefaultValueForUndefinedVariableRector::class => [
            __DIR__ . '/src/NeoTransposer/Domain/AutomaticTransposer.php',
        ],

        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/NeoTransposer/Domain/Entity/Book.php',
            __DIR__ . '/src/NeoTransposer/Domain/Entity/Song.php',
            __DIR__ . '/src/NeoTransposer/Domain/TranspositionChart.php'
        ],

        ClosureToArrowFunctionRector::class         => [
            __DIR__ . '/src/NeoTransposer/Infrastructure/AdminMetricsRepositoryMysql.php'
        ],

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
            __DIR__ . '/src/NeoTransposer/Domain/GeoIp/GeoIpException.php',
        ]
    ]);
};
