<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Model\User;

class UserRepositoryMysql extends MysqlRepository implements UserRepository
{
    public function readFromEmail(string $email): User
    {
        return new User();
    }

    public function readFromId(int $id): User
    {
        return new User();
    }

    public function readFromField(string $field, $value): User
    {
        return new User();
    }

}