<?php

namespace NeoTransposer\Domain\Service;

use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\UserNotExistException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;

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