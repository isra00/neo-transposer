<?php

namespace NeoTransposerApp\Application;

use NeoTransposerApp\Domain\Exception\BookNotExistException;
use NeoTransposerApp\Domain\Exception\UserNotExistException;
use NeoTransposerApp\Domain\Service\SongsLister;

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
    public function listSongsWithUserFeedbackAsArray(int $idBook, ?int $idUser): array
    {
        return $idUser
            ? $this->songsLister->readBookSongsWithUserFeedback($idBook, $idUser)->asArray()
            : $this->songsLister->readBookSongs($idBook)->asArray();
    }
}
