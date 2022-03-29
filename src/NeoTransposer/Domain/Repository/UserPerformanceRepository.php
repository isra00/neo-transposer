<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\ValueObject\UserPerformance;

interface UserPerformanceRepository
{
    public function readUserPerformance($idUser): UserPerformance;
}