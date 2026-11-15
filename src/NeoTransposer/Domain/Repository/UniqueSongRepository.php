<?php

namespace NeoTransposer\Domain\Repository;

interface UniqueSongRepository
{
    /**
     * Every unique song (the songs that point to the same origin song) with the songs
     * of each book that belong to it, for the songbook comparison.
     *
     * A book can hold more than one song of the same unique song (some books
     * print two arrangements of it), hence the list of songs per book.
     *
     * @return array<int, array{
     *     id_origin_song: int,
     *     name: string,
     *     id_origin_book: ?int,
     *     songs: array<int, mixed[][]>
     * }> Indexed by id_origin_song; the songs of each book indexed by id_book. The
     *    name is the origin song's title.
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
     * The songs of every book that point to the given origin song, itself included.
     *
     * @return mixed[][] Rows with id_song, id_book, title and is_origin.
     */
    public function readSongs(int $idOriginSong): array;
}
