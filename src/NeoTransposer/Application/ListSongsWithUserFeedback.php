<?php

namespace NeoTransposer\Application;

use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\UserNotExistException;
use NeoTransposer\Domain\Service\SongsLister;

final class ListSongsWithUserFeedback
{
    public function __construct(private readonly SongsLister $songsLister)
    {
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
