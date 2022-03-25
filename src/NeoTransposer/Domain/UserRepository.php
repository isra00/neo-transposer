<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Model\User;

interface UserRepository
{
    public function readFromEmail(string $email): ?User;
    public function readFromId(int $id): ?User;
    public function readFromField(string $field, $value): ?User;
}