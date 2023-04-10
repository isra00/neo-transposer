<?php

namespace NeoTransposer\Application;

use Silex\Application;

final class RunAdminTool
{
    public function __construct(protected Application $dependencyContainer)
    {
    }

    public function runAdminTask(string $adminTaskName): string
    {
        $toolsMethods = [
            'PopulateUsersCountry',
            'CheckSongsRangeConsistency',
            'CheckUsersRangeConsistency',
            'RefreshCompiledCss',
            'RemoveOldCompiledCss',
            'CheckChordsOrder',
            'TestAllTranspositions',
            'GetVoiceRangeOfGoodUsers',
            'CheckOrphanChords',
            'GetPerformanceByNumberOfFeedbacks',
            'CheckMissingTranslations'
        ];

        if (!in_array($adminTaskName, $toolsMethods)) {
            throw new AdminTaskNotExistException("Invalid task name $adminTaskName");
        }

        //This breaks Hexagonal Architecture. To fix it, we should have an Infrastructure\AdminTaskClassResolver doing this
        try {
            $taskObject = $this->dependencyContainer["NeoTransposer\\Domain\\AdminTasks\\$adminTaskName"];
        } catch (\Pimple\Exception\UnknownIdentifierException)
        {
            throw new AdminTaskNotExistException("Dependency container didn't find valid task name $adminTaskName");
        }

        return $taskObject->run();
    }
}