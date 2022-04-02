<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Model\Song;
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

    /**
     * Factory: get a Song object from the DB
     *
     * @param string $idSong Song ID or slug.
     * @return Song The requested Song object.
     * @throws \Exception If song does not exist or has an invalid id_book associated.
     *
     * @todo Refactor esto. Id or Slug es doble responsabilidad. Solo el controller debería aceptar ambos.
     */
	public function fetchSongByIdOrSlug(string $idSong): ?Song
    {
        $fieldId = 'slug';

        if (strval(intval($idSong)) === $idSong) {
            $fieldId = 'id_song';
            $idSong = (int)$idSong;
        }

        return $this->readSongByField($fieldId, $idSong);
    }

    /**
     * @throws \Exception
     */
    public function readSongByField(string $field, $value): ?Song
    {
		$songRow = $this->dbConnection->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $field = ?",
			[$value]
		);

		if (!$songRow) {
			throw new \Exception("The specified song does not exist or it's not bound to a valid book");
		}

		$originalChords = $this->dbConnection->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			[$songRow['id_song']]
		);

		return new Song(
            $songRow,
            array_map(
            function($row) {
                return Chord::fromString($row['chord']);
            }, $originalChords)
        );
	}

    public function readAllSongs(): array
    {
        return $this->dbConnection->fetchAll('SELECT * FROM song');
    }
}