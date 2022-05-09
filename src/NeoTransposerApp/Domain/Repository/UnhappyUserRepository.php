<?php

namespace NeoTransposerApp\Domain\Repository;

use NeoTransposerApp\Domain\Entity\User;

interface UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool;
    public function writeUnhappyUser(int $idUser): void;
    public function delete(int $idUser): void;
    public function readUserIsUnhappyAndNoAction(int $idUser): bool;
    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void;
}
