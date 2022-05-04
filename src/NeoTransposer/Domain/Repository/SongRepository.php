<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;

interface SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection;
    public function readBookSongs(int $idBook): SongsCollection;
    public function fetchSongByIdOrSlug(string $idSong): ?Song;
    public function readSongByField(string $field, $value): ?Song;
    public function readAllSongs(): array;
    public function createSong(
        int $idBook,
        int $page,
        string $title,
        string $lowestNote,
        string $highestNote,
        string $peopleLowestNote,
        string $peopleHighestNote,
        bool $firstChordIsNote,
        string $slug,
        array $chords
    ): void;

    public function slugAlreadyExists(string $slug): bool;
}