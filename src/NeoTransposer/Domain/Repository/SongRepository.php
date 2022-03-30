<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;
use NeoTransposer\Model\Song;

interface SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection;
    public function readBookSongs(int $idBook): SongsCollection;
    public function fetchSongByIdOrSlug(string $idSong): ?Song;
    public function readSongByField(string $field, $value): ?Song;
}