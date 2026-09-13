<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\UniqueSongRepository;

final class UniqueSongRepositoryMysql extends MysqlRepository implements UniqueSongRepository
{
    public function readComparison(): array
    {
        $rows = $this->dbConnection->select(
            'SELECT u.id_unique_song, u.name, u.id_origin_book, u.notes,
                    s.id_song, s.id_book, s.title, s.slug,
                    s.lowest_note, s.highest_note,
                    s.people_lowest_note, s.people_highest_note,
                    s.first_chord_is_tone,
                    (
                        SELECT GROUP_CONCAT(c.chord ORDER BY c.position SEPARATOR " ")
                        FROM song_chord c WHERE c.id_song = s.id_song
                    ) AS chords
             FROM unique_song u
             LEFT JOIN song s ON s.id_unique_song = u.id_unique_song
             ORDER BY u.name, s.id_book, s.id_song'
        );

        $comparison = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $idUniqueSong = (int) $row['id_unique_song'];

            $comparison[$idUniqueSong] ??= [
                'id_unique_song' => $idUniqueSong,
                'name'           => $row['name'],
                'id_origin_book' => $row['id_origin_book'] === null ? null : (int) $row['id_origin_book'],
                'notes'          => $row['notes'],
                'songs'          => [],
            ];

            // LEFT JOIN: a unique song whose songs were all deleted still has a row.
            if ($row['id_song'] === null) {
                continue;
            }

            $comparison[$idUniqueSong]['songs'][(int) $row['id_book']][] = $row;
        }

        return $comparison;
    }

    public function readBooksWithSongs(): array
    {
        return array_map(
            fn ($row) => (int) ((array) $row)['id_book'],
            $this->dbConnection->select('SELECT DISTINCT id_book FROM song ORDER BY id_book')
        );
    }
}
