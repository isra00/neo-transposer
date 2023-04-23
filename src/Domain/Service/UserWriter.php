<?php

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Exception\BadUserRangeException;
use App\Domain\Exception\BookNotExistException;
use App\Domain\Exception\InvalidStandardRangeException;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\NotesRange;

final class UserWriter
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