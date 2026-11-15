<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\UniqueSongRepository;

final class UniqueSongRepositoryMysql extends MysqlRepository implements UniqueSongRepository
{
    public function readComparison(): array
    {
        // LEFT JOIN: the songs of a group whose origin song was deleted still show up.
        $rows = $this->dbConnection->select(
            'SELECT s.id_origin_song, o.title AS name, o.id_book AS id_origin_book,
                    s.id_song, s.id_book, s.title, s.slug,
                    s.lowest_note, s.highest_note,
                    s.people_lowest_note, s.people_highest_note,
                    s.first_chord_is_tone, s.verified_at,
                    (
                        SELECT GROUP_CONCAT(c.chord ORDER BY c.position SEPARATOR " ")
                        FROM song_chord c WHERE c.id_song = s.id_song
                    ) AS chords
             FROM song s
             LEFT JOIN song o ON o.id_song = s.id_origin_song
             WHERE s.id_origin_song IS NOT NULL
             ORDER BY s.id_book, s.id_song'
        );

        $comparison = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $idOriginSong = (int) $row['id_origin_song'];

            $comparison[$idOriginSong] ??= [
                'id_origin_song' => $idOriginSong,
                'name'           => $row['name'] ?? $row['title'],
                'id_origin_book' => $row['id_origin_book'] === null ? null : (int) $row['id_origin_book'],
                'songs'          => [],
            ];

            $comparison[$idOriginSong]['songs'][(int) $row['id_book']][] = $row;
        }

        return $comparison;
    }

    public function readUnlinkedSongs(): array
    {
        return array_map(
            fn ($row) => (array) $row,
            $this->dbConnection->select(
                'SELECT s.id_song, s.id_book, s.title, s.slug,
                        s.lowest_note, s.highest_note,
                        s.people_lowest_note, s.people_highest_note,
                        s.first_chord_is_tone, s.verified_at,
                        (
                            SELECT GROUP_CONCAT(c.chord ORDER BY c.position SEPARATOR " ")
                            FROM song_chord c WHERE c.id_song = s.id_song
                        ) AS chords
                 FROM song s
                 WHERE s.id_origin_song IS NULL
                 AND NOT s.id_song IN (118, 319) -- Easter eggs, not songbook songs
                 ORDER BY s.title'
            )
        );
    }

    public function readBooksWithSongs(): array
    {
        return array_map(
            fn ($row) => (int) ((array) $row)['id_book'],
            $this->dbConnection->select('SELECT DISTINCT id_book FROM song ORDER BY id_book')
        );
    }

    public function readSongs(int $idOriginSong): array
    {
        return array_map(
            fn ($row) => (array) $row,
            $this->dbConnection->select(
                'SELECT id_song, id_book, title, id_song = id_origin_song AS is_origin
                 FROM song
                 WHERE id_origin_song = ?
                 ORDER BY id_book, id_song',
                [$idOriginSong]
            )
        );
    }
}
