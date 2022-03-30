<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\UserPerformance;
use NeoTransposer\Domain\Repository\UserPerformanceRepository;

class UserPerformanceRepositoryMysql extends MysqlRepository implements UserPerformanceRepository
{
    public function readUserPerformance($idUser): UserPerformance
    {
		$sql = <<<SQL
SELECT worked, COUNT(worked) count
FROM transposition_feedback
WHERE `id_user` = ?
GROUP BY worked
SQL;
		$result = $this->dbConnection->fetchAll($sql, [$idUser]);

		$performanceData = [0 => 0, 1 => 0];

		foreach ($result as $row)
		{
			$performanceData[(int) $row['worked']] = $row['count'];
		}

		$performance = (0 === array_sum($performanceData))
			? 0
			: $performanceData[1] / ($performanceData[0] + $performanceData[1]);

		return new UserPerformance(
			$performanceData[0] + $performanceData[1],
			$performance
		);
    }
}