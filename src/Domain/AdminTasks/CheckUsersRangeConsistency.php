<?php

namespace App\Domain\AdminTasks;

use App\Domain\NotesCalculator;
use App\Domain\Repository\UserRepository;

final class CheckUsersRangeConsistency implements AdminTask
{
    public function __construct(protected UserRepository $userRepository)
    {
    }
    
	public function run(): string
	{
		$users = $this->userRepository->readVoiceRangeFromAllUsers();

		$nc = new NotesCalculator();

		foreach ($users as $user)
		{
			if (!empty($user['lowest_note']) && !empty($user['highest_note']))
			{
				if ($user['lowest_note'] != $nc->lowestNote([$user['lowest_note'], $user['highest_note']]))
				{
					$output[] = '#' . $user['id_user'] . ' lowest ' . $user['lowest_note'] . ' > highest ' . $user['highest_note'] . '!';
				}

				if ($user['lowest_note'] == $user['highest_note'])
				{
					$output[] = '#' . $user['id_user'] . ' highest_note == lowest_note (' . $user['lowest_note'] . ')';
				}
			}
		}

		if (empty($output))
		{
			$output[] = 'NO inconsistencies found :-)';
		}

		return implode("\n", $output);
	}
}