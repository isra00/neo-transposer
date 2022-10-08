<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\User;

class LoginFlow
{

	/**
	 * Redirections depending on the state of the user:
     * - If user has no id -> login, unless we're in login.
     * - If user has no voice range -> user/voice, unless we're in $exempt.
	 *
	 */
	public static function redirectIfUserDoesNotComply(string $currentRoute, User $currentUser): ?string
	{
		//Login page has its own redirection logic.
		if ($currentRoute == 'login')
		{
			return null;
		}

		if (empty($currentUser->id_user))
		{
			return 'login';
		}

		if (!$currentUser->hasRange())
		{
			$exempt = array(
				'user_settings',
				'user_voice',
				'set_user_data',
				'wizard_step1',
				'wizard_select_standard',
				'wizard_empiric_lowest',
				'wizard_empiric_highest'
			);

			if (!in_array($currentRoute, $exempt))
			{
				return 'user_voice';
			}
		}

        return null;
	}
}
