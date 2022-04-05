<?php

namespace NeoTransposer\Domain\Service;

use NeoTransposer\Domain\BookNotExistException;
use NeoTransposer\Domain\InvalidStandardRangeException;
use NeoTransposer\Domain\BadUserRangeException;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Model\UnhappyUser;
use NeoTransposer\Model\User;

class UserWriter
{
    protected $userRepository;
    protected $allBooks;
    protected $unhappyUser;

    public function __construct(UserRepository $userRepository, array $allBooks, UnhappyUser $unhappyUser)
    {
        $this->userRepository = $userRepository;
        $this->allBooks = $allBooks;
        $this->unhappyUser = $unhappyUser;
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
            if (!in_array($idBook, array_keys($this->allBooks)))
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