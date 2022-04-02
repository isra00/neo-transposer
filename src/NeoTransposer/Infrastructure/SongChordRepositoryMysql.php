<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\SongChordRepository;

class SongChordRepositoryMysql extends MysqlRepository implements SongChordRepository
{

    public function readAllSongChordsInOrder(): array
    {
        return $this->dbConnection->fetchAll(
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
		return array_column($this->dbConnection->fetchAll($sql), 'id_song');
    }
}