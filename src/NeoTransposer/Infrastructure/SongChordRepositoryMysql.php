<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\SongChordRepository;
use NeoTransposer\Domain\ValueObject\Chord;

final class SongChordRepositoryMysql extends MysqlRepository implements SongChordRepository
{
    public function readAllSongChordsInOrder(): array
    {
        return (array)$this->dbConnection->select(
            'SELECT * FROM `song_chord` ORDER BY id_song ASC, position ASC'
        );
    }

    public function readSongsWithOrphanChords(): array
    {
		$sql = <<<SQL
SELECT song_chord.id_song id_song FROM song_chord
LEFT JOIN song ON song.id_song = song_chord.id_song
WHERE song.id_song IS NULL
SQL;
		return array_column((array)$this->dbConnection->select($sql), 'id_song');
    }

    public function readSongChords(int $idSong): array
    {
        $chordRows = (array)$this->dbConnection->select(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			[$idSong]
		);

        return array_map(
            fn($chordRow) => Chord::fromString($chordRow['chord']),
            $chordRows
        );

    }
}
