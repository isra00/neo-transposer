<?php

namespace NeoTransposer\Application;

use NeoTransposer\Domain\SongsLister;

class ListSongsWithUserFeedback
{
    private $songsLister;

    public function __construct(SongsLister $songsLister)
    {
        $this->songsLister = $songsLister;
    }

    /**
     * @throws \NeoTransposer\Domain\UserNotExistException
     */
    public function ListSongsWithUserFeedbackAsArray(int $idBook, ?int $idUser): array
    {
        return $idUser
            ? $this->songsLister->readBookSongsWithUserFeedback($idBook, $idUser)->asArray()
            : $this->songsLister->readBookSongs($idBook)->asArray();
    }
}