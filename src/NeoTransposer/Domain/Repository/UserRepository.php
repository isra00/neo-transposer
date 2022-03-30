<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Model\User;

interface UserRepository
{
    public function readFromId(int $idUser): ?User;
    public function readFromEmail(string $email): ?User;
    public function save(User $user, string $registerIp = null): ?int;
    public function saveWithVoiceChange(User $user, string $method): void;
}