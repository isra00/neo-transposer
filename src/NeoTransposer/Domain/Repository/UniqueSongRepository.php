<?php

namespace NeoTransposer\Domain\Repository;

interface UniqueSongRepository
{
    /**
     * Every unique song with the songs of each book that belong to it, for the
     * songbook comparison.
     *
     * A book can hold more than one song of the same unique song (some books
     * print two arrangements of it), hence the list of songs per book.
     *
     * @return array<int, array{
     *     id_unique_song: int,
     *     name: string,
     *     id_origin_book: ?int,
     *     notes: ?string,
     *     songs: array<int, mixed[][]>
     * }> Indexed by id_unique_song; the songs of each book indexed by id_book.
     */
    public function readComparison(): array;

    /**
     * Songs not yet linked to any unique song, with the same columns as the songs of
     * readComparison().
     *
     * @return mixed[][]
     */
    public function readUnlinkedSongs(): array;

    /**
     * Ids of the books that actually hold songs.
     *
     * @return int[]
     */
    public function readBooksWithSongs(): array;

    /**
     * The songs of every book that belong to the given unique song.
     *
     * @return mixed[][] Rows with id_song, id_book, title and is_origin (the song
     *                   is in the unique song's origin book).
     */
    public function readSongs(int $idUniqueSong): array;
}
