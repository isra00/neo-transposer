<?php

namespace NeoTransposerApp\Infrastructure;

use NeoTransposerApp\Domain\Repository\UnhappyUserRepository;
use NeoTransposerApp\Domain\Entity\User;

class UnhappyUserRepositoryMysql extends MysqlRepository implements UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool
    {
        return false !== $this->dbConnection->fetchColumn(
                'SELECT id_user FROM unhappy_user WHERE id_user = ?',
                [$idUser]
            );
    }

    public function readUserIsUnhappyAndNoAction(int $idUser): bool
    {
        return !empty(
            $this->dbConnection->fetchColumn(
                'SELECT id_user, took_action FROM unhappy_user WHERE id_user = ? AND took_action IS NULL',
                [$idUser]
            )
        );
    }

    public function writeUnhappyUser(int $idUser): void
    {
        //If user was already unhappy, UNIQUE will make query fail, nothing done.
        try {
            $this->dbConnection->insert('unhappy_user', ['id_user' => $idUser]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
        }
    }

    public function delete(int $idUser): void
    {
        $this->dbConnection->delete('unhappy_user', ['id_user' => $idUser]);
    }

    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void
    {
        $this->dbConnection->update(
            'unhappy_user',
            [
                'took_action' => date('Y-m-d H:i:s'),
                'action' => $action,
                'perf_before_action' => $performanceBeforeAction,
            ], ['id_user' => $idUser]
        );
    }
}