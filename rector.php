<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    $rectorConfig->sets([
//        LevelSetList::UP_TO_PHP_81,
//        SetList::PHP_81,
//        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
        SetList::CODE_QUALITY,
//        SetList::CODING_STYLE,
//        SetList::DEAD_CODE,
//        SetList::NAMING,
//        SetList::PRIVATIZATION,
//        SetList::TYPE_DECLARATION,
//        SetList::TYPE_DECLARATION_STRICT,
//        SetList::EARLY_RETURN,
    ]);
};
