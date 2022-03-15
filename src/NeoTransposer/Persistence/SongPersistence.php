<?php

namespace NeoTransposer\Persistence;

use Doctrine\DBAL\Connection;
use \NeoTransposer\Model\Song;

/**
 * Persistence layer for the Song entity.
 */
class SongPersistence
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @param Connection $db A DBAL connection.
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Factory: get a Song object from the DB
     *
     * @param string $idSong Song ID or slug.
     * @return Song The requested Song object.
     * @throws \Exception If song does not exist or has an invalid id_book associated.
     */
	public function fetchSongByIdOrSlug(string $idSong): Song
	{
		$fieldId = 'slug';

		if (preg_match('/^\d+$/', $idSong))
		{
			$fieldId = 'id_song';
			$idSong = (int) $idSong;
		}

		$songDetails = $this->db->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $fieldId = ?",
			array($idSong)
		);

		if (!$songDetails) {
			throw new \Exception("The specified song does not exist or it's not bound to a valid book");
		}

		$originalChords = $this->db->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array($songDetails['id_song'])
		);

		$originalChords = array_column($originalChords, 'chord');

		return new Song($songDetails, $originalChords);
	}
}
