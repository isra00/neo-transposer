<?php

namespace NeoTransposer\Persistence;

use Symfony\Component\HttpFoundation\Request;
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
		$field_id = 'slug';

		if (preg_match('/^\d+$/', $idSong))
		{
			$field_id = 'id_song';
			$idSong = (int) $idSong;
		}

		$song_details = $db->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $field_id = ?",
			array($idSong)
		);

		if (!$song_details) {
			throw new \Exception("The specified song does not exist or it's not bound to a valid book");
		}

		$original_chords = $db->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array($song_details['id_song'])
		);

		// In PHP 5.5 this can be implemented by array_column()
		array_walk($original_chords, function(&$item) {
			$item = $item['chord'];
		});

		return new Song($song_details, $original_chords);
	}
}