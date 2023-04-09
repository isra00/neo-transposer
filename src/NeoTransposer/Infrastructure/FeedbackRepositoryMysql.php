<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Domain\ValueObject\UserPerformance;

class FeedbackRepositoryMysql extends MysqlRepository implements FeedbackRepository
{
    public function readUserPerformance($idUser): UserPerformance
    {
		$sql = <<<SQL
SELECT worked, COUNT(worked) count
FROM transposition_feedback
WHERE `id_user` = ?
GROUP BY worked
SQL;
		$result = $this->dbConnection->fetchAllAssociative($sql, [$idUser]);

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

    public function createOrUpdateFeedback(
        int $idSong,
        int $idUser,
        bool $worked,
        NotesRange $userRange,
        string $pcStatus,
        float $centeredScoreRate,
        ?int $deviationFromCentered = null,
        ?string $transposition = null
    ): void {

		$sql = <<<SQL
INSERT INTO transposition_feedback (
	id_song,
	id_user,
	worked,
	user_lowest_note,
	user_highest_note,
	time,
	transposition,
	pc_status,
	centered_score_rate,
	deviation_from_center
) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
	id_song = ?,
	id_user = ?,
	worked = ?,
	user_lowest_note = ?,
	user_highest_note = ?,
	time = NOW(),
	transposition = ?,
	pc_status = ?,
	centered_score_rate = ?,
	deviation_from_center = ?
SQL;
		$this->dbConnection->executeUpdate($sql, [
			$idSong,
			$idUser,
            (int) $worked,
			$userRange->lowest(),
			$userRange->highest(),
			$transposition,
			$pcStatus,
			$centeredScoreRate,
			$deviationFromCentered,

			$idSong,
			$idUser,
            (int) $worked,
			$userRange->lowest(),
			$userRange->highest(),
			$transposition,
			$pcStatus,
			$centeredScoreRate,
			$deviationFromCentered
		]);
    }

    public function readSongFeedbackForUser(int $idUser, int $idSong): ?bool
    {
        $result = $this->dbConnection->fetchOne(
            'SELECT worked FROM transposition_feedback WHERE id_user = ? AND id_song = ?',
            [$idUser, $idSong]
        );

        return strlen((string) $result) ? (bool) $result : null;
    }
}