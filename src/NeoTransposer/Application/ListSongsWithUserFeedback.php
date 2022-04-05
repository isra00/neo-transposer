<?php

namespace NeoTransposer\Application;

use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\UserNotExistException;
use NeoTransposer\Domain\Service\SongsLister;

class ListSongsWithUserFeedback
{
    private $songsLister;

    public function __construct(SongsLister $songsLister)
    {
        $this->songsLister = $songsLister;
    }

    /**
     * @throws UserNotExistException
     * @throws BookNotExistException
     */
    public function ListSongsWithUserFeedbackAsArray(int $idBook, ?int $idUser): array
    {
        return $idUser
            ? $this->songsLister->readBookSongsWithUserFeedback($idBook, $idUser)->asArray()
            : $this->songsLister->readBookSongs($idBook)->asArray();
    }
}