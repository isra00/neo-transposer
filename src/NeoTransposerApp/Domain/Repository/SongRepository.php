<?php

namespace NeoTransposerApp\Domain\Repository;

use NeoTransposerApp\Domain\Entity\Song;
use NeoTransposerApp\Domain\SongsCollection;
use NeoTransposerApp\Domain\SongsWithUserFeedbackCollection;

interface SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection;
    public function readBookSongs(int $idBook): SongsCollection;
    public function fetchSongByIdOrSlug(string $idSong): ?Song;
    public function readSongByField(string $field, $value): ?Song;
    public function readAllSongs(): array;
    public function createSong(Song $song): void;

    public function slugAlreadyExists(string $slug): bool;
}
