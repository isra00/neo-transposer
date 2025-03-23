<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\UnhappyUserRepository;
use NeoTransposer\Domain\Entity\User;

final class UnhappyUserRepositoryMysql extends MysqlRepository implements UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool
    {
        return false !== $this->dbConnection->fetchOne(
                'SELECT id_user FROM unhappy_user WHERE id_user = ?',
                [$idUser]
            );
    }

    public function readUserIsUnhappyAndNoAction(int $idUser): bool
    {
        return !empty(
            $this->dbConnection->selectOne(
                'SELECT id_user, took_action FROM unhappy_user WHERE id_user = ? AND took_action IS NULL',
                [$idUser]
            )
        );
    }

    public function writeUnhappyUser(int $idUser): void
    {
        //If user was already unhappy, UNIQUE will make query fail, nothing done.
        try {
            self::dbal()->insert('unhappy_user', ['id_user' => $idUser]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
        }
    }

    public function delete(int $idUser): void
    {
        $this->dbConnection->delete('unhappy_user', ['id_user' => $idUser]);
    }

    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void
    {
        self::dbal()->update(
            'unhappy_user',
            [
                'took_action' => date('Y-m-d H:i:s'),
                'action' => $action,
                'perf_before_action' => $performanceBeforeAction,
            ], ['id_user' => $idUser]
        );
    }
}
