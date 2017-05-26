<?php

namespace NeoTransposer\Persistence;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\User;

/**
 * Persistence layer for the User entity.
 */
class UserPersistence
{
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $db;

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
		$sql = 'SELECT * FROM user WHERE email LIKE ?';
		
		if ($userdata = $this->db->fetchAssoc($sql, array($email)))
		{
			return new User(
				$userdata['email'],
				$userdata['id_user'],
				$userdata['lowest_note'],
				$userdata['highest_note'],
				$userdata['id_book'],
				$userdata['wizard_step1'],
				$userdata['wizard_lowest_attempts'],
				$userdata['wizard_highest_attempts'],
				$userdata['is_unhappy'],
				$userdata['chose_std']
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
					'lowest_note'	=> $user->lowest_note,
					'highest_note'	=> $user->highest_note,
					'id_book'		=> $user->id_book,
					'wizard_step1' 	=> $user->wizard_step1,
					'wizard_lowest_attempts' => $user->wizard_lowest_attempts,
					'wizard_highest_attempts' => $user->wizard_highest_attempts,
					'is_unhappy' 	=> $user->isUnhappy,
					'chose_std' 	=> $user->choseStd,
				], ['id_user' => (int) $user->id_user]
			);
		}

		$this->db->insert('user', array(
			'email'			=> $user->email,
			'lowest_note'	=> $user->lowest_note,
			'highest_note'	=> $user->highest_note,
			'id_book'		=> $user->id_book,
			'register_ip'	=> $registerIp
		));

		return $user->id_user = $this->db->lastInsertId();
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

		return [
			'performance' 	=> $performanceData[1] / ($performanceData[0] + $performanceData[1]),
			'reports'		=> $performanceData[0] + $performanceData[1],
		];
	}
}
