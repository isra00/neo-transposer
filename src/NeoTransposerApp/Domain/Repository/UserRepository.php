<?php

namespace NeoTransposerApp\Domain\Repository;

use NeoTransposerApp\Domain\Entity\User;

interface UserRepository
{
    public function readFromId(int $idUser): ?User;
    public function readFromEmail(string $email): ?User;
    public function readIpFromUsersWithNullCountry(): array;
    public function readVoiceRangeFromAllUsers(): array;
    public function save(User $user, string $registerIp = null): ?int;
    public function saveWithVoiceChange(User $user, string $method): void;
    public function saveUserCountryByIp(string $countryIsoCode, string $ip): void;
}
