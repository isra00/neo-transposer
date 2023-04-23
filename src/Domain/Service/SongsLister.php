<?php

namespace App\Domain\Service;

use App\Domain\Exception\BookNotExistException;
use App\Domain\Exception\UserNotExistException;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\SongRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\SongsCollection;
use App\Domain\SongsWithUserFeedbackCollection;

final class SongsLister
{
    public function __construct(
        protected SongRepository $songRepository,
        protected UserRepository $userRepository,
        private readonly BookRepository $bookRepository)
    {
    }

    /**
     * @throws UserNotExistException|BookNotExistException
     */
    public function readBookSongsWithUserFeedback(int $idBook, int $idUser): SongsWithUserFeedbackCollection
    {
        if (empty($this->userRepository->readFromId($idUser))) {
            throw new UserNotExistException($idUser);
        }

        if (empty($this->bookRepository->readBook($idBook)))
        {
            throw new BookNotExistException($idBook);
        }

        return $this->songRepository->readBookSongsWithUserFeedback($idBook, $idUser);
    }

    /**
     * @throws BookNotExistException
     */
    public function readBookSongs(int $idBook): SongsCollection
    {
        if (empty($this->bookRepository->readBook($idBook)))
        {
            throw new BookNotExistException($idBook);
        }

        return $this->songRepository->readBookSongs($idBook);
    }
}