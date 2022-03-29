<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;

class SongRepositoryMysql extends MysqlRepository implements SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection
    {
		$sql = <<<SQL
SELECT song.id_song, slug, page, title, transposition_feedback.worked
FROM song
LEFT JOIN transposition_feedback
	ON transposition_feedback.id_song = song.id_song
	AND transposition_feedback.id_user = ?
WHERE id_book = ?
AND NOT song.id_song IN (118, 319)
ORDER BY page, title
SQL;

		$songs = $this->dbConnection->fetchAll($sql, [$idUser, $idBook]);

        return new SongsWithUserFeedbackCollection($songs);
    }

    public function readBookSongs(int $idBook): SongsCollection
    {
		$sql = <<<SQL
SELECT song.id_song, slug, page, title
FROM song
WHERE id_book = ?
AND NOT song.id_song IN (118, 319)
ORDER BY page, title
SQL;

        return new SongsCollection($this->dbConnection->fetchAll($sql, [$idBook]));
    }
}