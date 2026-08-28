<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\UnhappyUserRepository;

final class UnhappyUserRepositoryMysql extends MysqlRepository implements UnhappyUserRepository
{
    public function readUserIsUnhappy(int $idUser): bool
    {
        return $this->dbConnection->selectOne(
            'SELECT id_user FROM unhappy_user WHERE id_user = ?',
            [$idUser]
        ) !== null;
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
        // If user was already unhappy, UNIQUE would make the query fail, so ignore it.
        $this->dbConnection->table('unhappy_user')->insertOrIgnore(['id_user' => $idUser]);
    }

    public function delete(int $idUser): void
    {
        $this->dbConnection->table('unhappy_user')->where('id_user', $idUser)->delete();
    }

    public function updateUnhappyUser(string $action, float $performanceBeforeAction, int $idUser): void
    {
        $this->dbConnection->table('unhappy_user')->where('id_user', $idUser)->update([
            'took_action' => date('Y-m-d H:i:s'),
            'action' => $action,
            'perf_before_action' => $performanceBeforeAction,
        ]);
    }
}
