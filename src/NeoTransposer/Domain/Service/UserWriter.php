<?php

namespace NeoTransposer\Domain\Service;

use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\Exception\BadUserRangeException;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\InvalidStandardRangeException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;

class UserWriter
{
    public function __construct(
        protected UserRepository $userRepository,
        protected BookRepository $bookRepository,
        protected UnhappinessManager $unhappyUser)
    {
    }

    /**
     * @throws BookNotExistException
     * @throws InvalidStandardRangeException
     * @throws BadUserRangeException
     */
    public function writeUser(User $user, ?int $idBook, ?string $lowest, ?string $highest, ?string $unhappyChoseStandardRange)
    {
        if ($idBook)
        {
            if (!array_key_exists($idBook, $this->bookRepository->readAllBooks()))
			{
				throw new BookNotExistException($idBook);
			}

            $user->id_book = $idBook;
        }

        if ($lowest || $highest) {

            /** @todo En PHP 8, str_contains() */
            if (strpos($highest, '1')) {
                throw new BadUserRangeException('Highest note is too low: ' . $highest);
            }

            $user->range = new NotesRange($lowest, $highest);
        }

		if ($unhappyChoseStandardRange)
		{
            $this->unhappyUser->chooseStandard($user, $unhappyChoseStandardRange);
		}

        $this->userRepository->saveWithVoiceChange(
            $user,
			$unhappyChoseStandardRange ? User::METHOD_UNHAPPY : User::METHOD_MANUAL
		);
    }
}