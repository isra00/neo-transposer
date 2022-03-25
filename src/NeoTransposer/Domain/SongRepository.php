<?php

namespace NeoTransposer\Domain;

interface SongRepository
{
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection;
    public function readBookSongs(int $idBook): SongsCollection;
}