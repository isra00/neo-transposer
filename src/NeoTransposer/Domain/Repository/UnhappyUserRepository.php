<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\Entity\User;

interface UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool;
    public function writeUnhappyUser(int $idUser): void;
    public function delete(int $idUser): void;
    public function readUserIsUnhappyAndNoAction(int $idUser): bool;
    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void;
}