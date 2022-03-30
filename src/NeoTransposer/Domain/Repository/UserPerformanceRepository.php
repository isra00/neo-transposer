<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\Entity\UserPerformance;

interface UserPerformanceRepository
{
    public function readUserPerformance($idUser): UserPerformance;
}