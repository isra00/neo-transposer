<?php

namespace NeoTransposerApp\Domain\Service;

use NeoTransposerApp\Domain\Exception\BookNotExistException;
use NeoTransposerApp\Domain\Exception\UserNotExistException;
use NeoTransposerApp\Domain\Repository\BookRepository;
use NeoTransposerApp\Domain\Repository\SongRepository;
use NeoTransposerApp\Domain\Repository\UserRepository;
use NeoTransposerApp\Domain\SongsCollection;
use NeoTransposerApp\Domain\SongsWithUserFeedbackCollection;

class SongsLister
{
    protected $songRepository;
    protected $userRepository;
    /**
     * @var BookRepository
     */
    private $bookRepository;

    public function __construct(SongRepository $songRepository, UserRepository $userRepository, BookRepository $bookRepository)
    {
        $this->songRepository = $songRepository;
        $this->userRepository = $userRepository;
        $this->bookRepository = $bookRepository;
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
