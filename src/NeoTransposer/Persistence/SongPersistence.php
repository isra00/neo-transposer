<?php

namespace NeoTransposer\Persistence;

use \NeoTransposer\Model\Song;

/**
 * Persistence layer for the Song entity.
 */
class SongPersistence
{

	/**
	 * Factory: get a Song object from the DB
	 * 
	 * @param  string 						$idSong Song ID or slug.
	 * @param  \Doctrine\DBAL\Connection 	$db 	Database connection.
	 * @return Song        					The requested Song object.
	 */
	public static function fetchSongById($idSong, \Doctrine\DBAL\Connection $db)
	{
		$fieldId = 'slug';

		if (preg_match('/^\d+$/', $idSong))
		{
			$fieldId = 'id_song';
			$idSong = (int) $idSong;
		}

		$songDetails = $db->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $fieldId = ?",
			array($idSong)
		);

		if (!$songDetails) {
			throw new \Exception("The specified song does not exist or it's not bound to a valid book");
		}

		$originalChords = $db->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array($songDetails['id_song'])
		);

		$originalChords = array_column($originalChords, 'chord');

		return new Song($songDetails, $originalChords);
	}
}
