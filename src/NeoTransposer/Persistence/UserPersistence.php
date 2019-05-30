<?php

namespace NeoTransposer\Persistence;

use \NeoTransposer\Model\User;
use \NeoTransposer\Model\NotesRange;

/**
 * Persistence layer for the User entity.
 */
class UserPersistence
{
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $db;

	const METHOD_WIZARD  = 'wizard';
	const METHOD_MANUAL  = 'manual';
	const METHOD_UNHAPPY = 'auto_unhappy';

	/**
	 * @param \Doctrine\DBAL\Connection $db A DBAL connection.
	 */
	public function __construct(\Doctrine\DBAL\Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * Factory: get a User object from the DB
	 * 
	 * @param  string 						$email 	User e-mail
	 * @return User        					The User instance for that e-mail.
	 */
	public function fetchUserFromEmail($email)
	{
		return $this->fetchUserFromField('email', $email);
	}

	public function fetchUserFromField($field, $fieldValue)
	{
		if (false === array_search($field, ['email', 'id_user']))
		{
			throw new \InvalidArgumentException('Only email and id_user are accepted');
		}

		$sql = "SELECT * FROM user WHERE $field LIKE ?";
		
		if ($userdata = $this->db->fetchAssoc($sql, array($fieldValue)))
		{
			return new User(
				$userdata['email'],
				$userdata['id_user'],
				new NotesRange($userdata['lowest_note'], $userdata['highest_note']),
				$userdata['id_book'],
				$userdata['wizard_step1'],
				$userdata['wizard_lowest_attempts'],
				$userdata['wizard_highest_attempts'],
				$this->db->fetchColumn('SELECT COUNT(DISTINCT id_song) FROM transposition_feedback WHERE id_user = ?', [$userdata['id_user']])
			);
		}
	}

	/**
	 * Create or update the user in the database.
	 * 
	 * @param  NeoTransposer\Model\User 			$user 	The User object to persist.
	 * @param  string $registerIp The IP address with which the user registered.
	 * @return integer The user ID, if it was not set.
	 */
	public function persist(User $user, $registerIp = null)
	{
		if ($user->id_user)
		{
			return $this->db->update('user',
				[
					'lowest_note'	=> $user->range->lowest ?? null,
					'highest_note'	=> $user->range->highest ?? null,
					'id_book'		=> $user->id_book,
					'wizard_step1' 	=> $user->wizard_step1,
					'wizard_lowest_attempts' => $user->wizard_lowest_attempts,
					'wizard_highest_attempts' => $user->wizard_highest_attempts,
				], ['id_user' => (int) $user->id_user]
			);
		}

		$this->db->insert('user', array(
			'email'			=> $user->email,
			'lowest_note'	=> $user->range->lowest ?? null,
			'highest_note'	=> $user->range->highest ?? null,
			'id_book'		=> $user->id_book,
			'register_ip'	=> $registerIp
		));

		return $user->id_user = $this->db->lastInsertId();
	}


	/**
	 * Update the user logging the voice range change
	 * 
	 * @param  NeoTransposer\Model\User 			$user 	The User object to persist.
	 * @param  string $registerIp 	The IP address with which the user registered.
	 * @param  string $method 		Either 'wizard' or 'manual'.
	 * @return boolean True if the voice range has changed, false if user had no voice range
	 */
	public function persistWithVoiceChange(User $user, $registerIp = null, $method=self::METHOD_WIZARD)
	{
		if (empty($user->id_user))
		{
			throw new \InvalidArgumentException('The user must have an ID');
		}

		if (false === array_search($method, [self::METHOD_WIZARD, self::METHOD_MANUAL, self::METHOD_UNHAPPY]))
		{
			throw new \InvalidArgumentException("Invalid voice range update method '$method'");
		}

		$currentVoiceRange = $this->db->fetchAssoc('SELECT lowest_note, highest_note FROM user WHERE id_user = ?', [$user->id_user]);

		$previouslyHadVoiceRange = false;

		if (!empty($currentVoiceRange['lowest_note']))
		{
			$previouslyHadVoiceRange = true;

			$this->db->insert('log_voice_range', array(
				'id_user'		=> $user->id_user,
				'method'		=> $method,
				'lowest_note'	=> $user->range->lowest,
				'highest_note'	=> $user->range->highest
			));
		}

		$this->persist($user, $registerIp);

		return $previouslyHadVoiceRange;
	}


	public function fetchUserPerformance(User $user)
	{
		$sql = <<<SQL
SELECT worked, COUNT(worked) count
FROM transposition_feedback
WHERE `id_user` = ?
GROUP BY worked
SQL;
		$result = $this->db->fetchAll($sql, [$user->id_user]);

		$performanceData = [0=>0, 1=>0];

		foreach ($result as $row)
		{
			$performanceData[(int) $row['worked']] = $row['count'];
		}

		$performance = (0 === array_sum($performanceData))
			? 0
			: $performanceData[1] / ($performanceData[0] + $performanceData[1]);

		return [
			'performance' 	=> $performance,
			'reports'		=> $performanceData[0] + $performanceData[1],
		];
	}
}
