<?php

namespace NeoTransposer\Domain\Repository;

interface SongChordRepository
{
    public function readAllSongChordsInOrder(): array;
    public function readSongsWithOrphanChords(): array;
    public function readSongChords(int $idSong): array;
}
