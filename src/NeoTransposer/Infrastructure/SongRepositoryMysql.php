<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\Exception\SongNotExistException;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;
use NeoTransposer\Domain\ValueObject\Chord;

final class SongRepositoryMysql extends MysqlRepository implements SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection
    {
        //These 2 book columns are still needed by AllSongsReport
		$sql = <<<SQL
SELECT song.*, transposition_feedback.worked, transposition_feedback.transposition transposition_which_worked, book.chord_printer, book.locale, id_book
FROM song
JOIN book USING (id_book)
LEFT JOIN transposition_feedback
	ON transposition_feedback.id_song = song.id_song
	AND transposition_feedback.id_user = ?
WHERE id_book = ?
AND NOT song.id_song IN (118, 319)
ORDER BY page, title
SQL;

		$songs = $this->dbConnection->fetchAllAssociative($sql, [$idUser, $idBook]);

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

        return new SongsCollection($this->dbConnection->fetchAllAssociative($sql, [$idBook]));
    }

    /**
     * Factory: get a Song object from the DB
     *
     * @param string $idSong Song ID or slug.
     * @return Song The requested Song object.
     * @throws SongNotExistException If song does not exist or has an invalid id_book associated.
     *
     * @todo Refactor esto. Id or Slug es doble responsabilidad. Solo el controller debería aceptar ambos.
     */
	public function fetchSongByIdOrSlug(string $idSong): ?Song
    {
        $fieldId = 'slug';

        if ((string) (int) $idSong === $idSong) {
            $fieldId = 'id_song';
            $idSong = (int)$idSong;
        }

        return $this->readSongByField($fieldId, $idSong);
    }

    /**
     * @throws SongNotExistException
     */
    public function readSongByField(string $field, $value): ?Song
    {
        /** @refactor SELECT * FROM 2 tablas?? Disgregar lo que hace falta de book y lo que no */
		$songRow = $this->dbConnection->fetchAssociative(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $field = ?",
			[$value]
		);

		if (!$songRow) {
			throw new SongNotExistException("The specified song does not exist or it's not bound to a valid book");
		}

        /** @refactor Replace by SongChordRepository::readSongChords() */
		$originalChords = $this->dbConnection->fetchAllAssociative(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			[$songRow['id_song']]
		);

		return new Song(
            $songRow,
            array_map(
            fn($row) => Chord::fromString($row['chord']), $originalChords)
        );
	}

    public function readAllSongs(): array
    {
        return $this->dbConnection->fetchAllAssociative('SELECT * FROM song');
    }

    public function createSong(
        int $idBook,
        ?int $page,
        string $title,
        string $lowestNote,
        string $highestNote,
        string $peopleLowestNote,
        string $peopleHighestNote,
        bool $firstChordIsNote,
        string $slug,
        array $chords,
        ?string $url = null
    ): void {

        $this->dbConnection->insert('song', [
			'id_book' 				=> $idBook,
			'page' 					=> $page,
			'title' 				=> $title,
			'lowest_note' 			=> $lowestNote,
			'highest_note' 			=> $highestNote,
			'people_lowest_note' 	=> $peopleLowestNote,
			'people_highest_note' 	=> $peopleHighestNote,
			'first_chord_is_tone' 	=> $firstChordIsNote,
			'slug'	 				=> $slug,
			'url'	 				=> $url
		]);

		$idSong = $this->dbConnection->lastInsertId();

		foreach ($chords as $position=>$chord)
		{
			if ($chord != '')
			{
				$this->dbConnection->insert('song_chord', [
                    'id_song'  => $idSong,
                    'chord'    => $chord,
                    'position' => $position
                ]);
			}
		}
    }

    public function slugAlreadyExists(string $slug): bool
    {
        return !empty($this->dbConnection->fetchAssociative(
			'SELECT id_song, slug FROM song WHERE slug = ?',
			[$slug]
		));
    }
}