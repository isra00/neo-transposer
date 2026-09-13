<?php

namespace NeoTransposer\Domain\Repository;

interface UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool;

    public function writeUnhappyUser(int $idUser): void;

    public function delete(int $idUser): void;

    public function readUserIsUnhappyAndNoAction(int $idUser): bool;

    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void;
}
