<?php

namespace NeoTransposer\Model;

use \NeoTransposer\Persistence\UserPersistence;

class UnhappyUser extends \NeoTransposer\AppAccess
{
	/**
	 * The performance below which a user is considered unhappy.
	 * 
	 * @var float
	 */
	const UNHAPPY_THRESHOLD_PERF = .5;

	/**
	 * The minimum number of feedback reports for considering a user unhappy if 
	 * their performance < UNHAPPY_THRESHOLD_PERF
	 * 
	 * @var int
	 */
	const UNHAPPY_THRESHOLD_REPORTS = 5;

	public function setUnhappy(User $user)
	{
		$userPersistence = new UserPersistence($this->app['db']);

		$performance = $userPersistence->fetchUserPerformance($user);

		if ($performance['performance'] < self::UNHAPPY_THRESHOLD_PERF && $performance['reports'] >= self::UNHAPPY_THRESHOLD_REPORTS)
		{
			//If user was already unhappy, UNIQUE will make query fail, nothing done.
			try {
				$this->app['db']->insert('unhappy_user', ['id_user' => $user->id_user]);
			} 
			catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {}
		}
		else
		{
			//If user was unhappy with no action but their performance is good, delete unhappy.
			if ($this->isUnhappyNoAction($user))
			{
				$this->app['db']->delete('unhappy_user', ['id_user'=>$user->id_user]);
			}
		}
	}

	public function isUnhappy(User $user)
	{
		return false !== $this->app['db']->fetchColumn('SELECT id_user FROM unhappy_user WHERE id_user = ?', [$user->id_user]);
	}

	/**
	 * Whether the user is unhappy and has taken no action so far.
	 */
	public function isUnhappyNoAction(User $user) : bool
	{
		$tookAction = $this->app['db']->fetchColumn('SELECT id_user, took_action FROM unhappy_user WHERE id_user = ? AND took_action IS NULL', [$user->id_user]);
		return !empty($tookAction);
	}

	/**
	 * Wizard finished debe llamar a este método.
	 */
	public function changedVoiceRangeFromWizard(User $user)
	{
		if ($this->isUnhappyNoAction($user))
		{
			$this->takeAction($user, 'wizard');
		}
	}

	public function chooseStandard(User $user, string $standard)
	{
		$standardVoices = array_keys($this->app['neoconfig']['voice_wizard']['standard_voices']);

		if (false === array_search($standard, $standardVoices))
		{
			throw new \UnexpectedValueException("Invalid standard voice $standard");
		}

		$this->takeAction($user, 'std_' . $standard);
	}

	public function takeAction(User $user, string $action)
	{
		$userPersistence = new UserPersistence($this->app['db']);

		$this->app['db']->update(
			'unhappy_user',
			[
				'took_action'			=> date('Y-m-d H:i:s'),
				'action'				=> $action,
				'perf_before_action'	=> $userPersistence->fetchUserPerformance($user)['performance'],
			], ['id_user' => (int) $user->id_user]
		);
	}
}
